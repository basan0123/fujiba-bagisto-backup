#!/usr/bin/env python3
"""
Caption-style research (WS-K): how REAL sellers write post text in Burmese (my),
Nepali (ne), Japanese (ja) and English (en), so our captions can sound like a
shop owner instead of a template. STAGING ONLY: nothing here posts, queues or
edits v1 captions.

Pure text analysis: no DB, no network. The DB side is caption_style_db.php.

  caption_style.py inbox    --dir /root/incoming/caption-screens --out inbox.json [--ocr]
  caption_style.py analyse  --bundle bundle.json --content content.json --out analysis.json [--inbox inbox.json]
  caption_style.py render   --analysis analysis.json --content content.json --outdir DIR
  caption_style.py check    --analysis analysis.json --text-file FILE [--fresh]
  caption_style.py selftest

Rules enforced here (documented in docs/intelligence/caption-style-method.md):
  * copy guard: generated text may not share a run of >=6 words, >=20 Burmese
    characters or >=12 Japanese characters with ANY collected text;
  * Burmese output must be Unicode (heuristic Zawgyi detector below);
  * drafts: no 細布, no width/length, no supplier codes, price ¥1,595 only,
    Messenger link present, sold-out drafts carry 'DO NOT POST until restocked'.
Image/OCR work (inbox --ocr) acquires heavy_guard before touching any image.
"""
import argparse
import collections
import hashlib
import json
import os
import re
import statistics
import subprocess
import sys
from datetime import datetime, timezone

METHOD_VERSION = 'cs-1.0'
AI_THRESHOLD = 0.40          # ai_likeness >= this -> AI-looking (sample excluded / segment ai_sounding)
BORDERLINE = 0.25
MIN_ESTABLISHED = 10         # included human samples per language before a style stops being provisional
LANGS = ('en', 'ja', 'my', 'ne')
HEAVY_DIR = '/var/www/bagisto/packages/Fujiba/AutoPoster/bin/intel'
MESSENGER = 'https://m.me/103196179075087'
DNP = 'DO NOT POST until restocked'

# ------------------------------------------------------------------ scripts
MY_CHARS = 'က-႟ꧠ-꧿ꩠ-ꩿ'
MY_RE = re.compile('[' + MY_CHARS + ']')
DEV_RE = re.compile('[ऀ-ॿ]')
JA_CHARS = '぀-ヿㇰ-ㇿ㐀-䶿一-鿿ｦ-ﾟ々〆'
JA_RE = re.compile('[' + JA_CHARS + ']')
LAT_RE = re.compile('[A-Za-zÀ-ɏ]')
FANCY_RE = re.compile('[\U0001D400-\U0001D7FF]')
URL_RE = re.compile(r'https?://\S+|\bm\.me/\S+|\bwww\.\S+')
MENTION_RE = re.compile(r'@\[[^\]]*\]|@[A-Za-z0-9_.]+')
HASHTAG_RE = re.compile(r'[#＃]([^\s#＃@,!?;:()\[\]{}"\'、。！？「」]+)')
CODE_RE = re.compile(r'\bF[A-Z]-[A-Z0-9]+(?:-[A-Z0-9]+)*(?:\(3m\))?|\bKIMONO-\d+\b')
SUPPLIER_CODE_RE = re.compile(r'\b1\d{3}-\d{2,3}-?[A-Z]\b')
PRICE_RE = re.compile(r'[¥￥]\s?\d[\d,]*|\d[\d,]*\s?(?:円|yen\b|ယန်း)|matra\s*:?\s*\d{3,5}', re.I)
PHONE_RE = re.compile(r'(?<!\d)(?:\+?\d[\d\s-]{8,}\d)(?!\d)')
EM_DASH_RE = re.compile(r'\s[—–]\s|[—–]')
MID_DOT_RE = re.compile(r' · |(?<=[^\s\n])・(?=[^\s\n])')
FLAG_LINE_RE = re.compile('^\\s*[\U0001F1E6-\U0001F1FF]{2}', re.M)
WORD_RE = re.compile(r"[A-Za-zÀ-ɏ][A-Za-zÀ-ɏ'’]*")

EMOJI_SINGLE = {0x00A9, 0x00AE, 0x203C, 0x2049, 0x2122, 0x2139, 0x24C2, 0x25B6, 0x25C0, 0x2934, 0x2935,
                0x3030, 0x303D, 0x3297, 0x3299, 0x2B50, 0x2B55, 0x2B1B, 0x2B1C, 0x2B05, 0x2B06, 0x2B07,
                0x1F004, 0x1F0CF, 0x1F18E, 0x1F21A, 0x1F22F}
EMOJI_RANGES = [(0x1F300, 0x1F5FF), (0x1F600, 0x1F64F), (0x1F680, 0x1F6FF), (0x1F7E0, 0x1F7EB),
                (0x1F90C, 0x1F9FF), (0x1FA70, 0x1FAFF), (0x231A, 0x231B), (0x23E9, 0x23F3),
                (0x23F8, 0x23FA), (0x2194, 0x2199), (0x21A9, 0x21AA), (0x25AA, 0x25AB), (0x25FB, 0x25FE),
                (0x1F170, 0x1F171), (0x1F17E, 0x1F17F), (0x1F191, 0x1F19A), (0x1F201, 0x1F202),
                (0x1F232, 0x1F23A), (0x1F250, 0x1F251),
                # U+2600-27BF: only code points that are emoji (others like ♪ ☆ ♡ are 'decor')
                (0x2600, 0x2604), (0x260E, 0x260E), (0x2611, 0x2611), (0x2614, 0x2615), (0x2618, 0x2618),
                (0x261D, 0x261D), (0x2620, 0x2620), (0x2622, 0x2623), (0x2626, 0x2626), (0x262A, 0x262A),
                (0x262E, 0x262F), (0x2638, 0x263A), (0x2640, 0x2640), (0x2642, 0x2642), (0x2648, 0x2653),
                (0x265F, 0x2660), (0x2663, 0x2663), (0x2665, 0x2666), (0x2668, 0x2668), (0x267B, 0x267B),
                (0x267E, 0x267F), (0x2692, 0x2697), (0x2699, 0x2699), (0x269B, 0x269C), (0x26A0, 0x26A1),
                (0x26A7, 0x26A7), (0x26AA, 0x26AB), (0x26B0, 0x26B1), (0x26BD, 0x26BE), (0x26C4, 0x26C5),
                (0x26C8, 0x26C8), (0x26CE, 0x26CF), (0x26D1, 0x26D1), (0x26D3, 0x26D4), (0x26E9, 0x26EA),
                (0x26F0, 0x26F5), (0x26F7, 0x26FA), (0x26FD, 0x26FD), (0x2702, 0x2702), (0x2705, 0x2705),
                (0x2708, 0x270D), (0x270F, 0x270F), (0x2712, 0x2712), (0x2714, 0x2714), (0x2716, 0x2716),
                (0x271D, 0x271D), (0x2721, 0x2721), (0x2728, 0x2728), (0x2733, 0x2734), (0x2744, 0x2744),
                (0x2747, 0x2747), (0x274C, 0x274C), (0x274E, 0x274E), (0x2753, 0x2755), (0x2757, 0x2757),
                (0x2763, 0x2764), (0x2795, 0x2797), (0x27A1, 0x27A1), (0x27B0, 0x27B0), (0x27BF, 0x27BF)]
DECOR = set('♪♫♡☆★✿❀◆◇■□●○►✓•')


def is_emoji_cp(cp):
    if cp in EMOJI_SINGLE:
        return True
    return any(a <= cp <= b for a, b in EMOJI_RANGES)


def extract_emoji(text):
    """Emoji as users see them: flags, keycaps, ZWJ sequences and skin tones kept whole; FE0F dropped."""
    out, i, n = [], 0, len(text or '')
    while i < n:
        cp = ord(text[i])
        if 0x1F1E6 <= cp <= 0x1F1FF:
            if i + 1 < n and 0x1F1E6 <= ord(text[i + 1]) <= 0x1F1FF:
                out.append(text[i:i + 2])
                i += 2
                continue
            i += 1
            continue
        if text[i] in '0123456789#*' and i + 1 < n and (
                text[i + 1] == '⃣' or (text[i + 1] == '️' and i + 2 < n and text[i + 2] == '⃣')):
            j = i + (3 if text[i + 1] == '️' else 2)
            out.append(text[i:j].replace('️', ''))
            i = j
            continue
        if is_emoji_cp(cp) and not (0x1F3FB <= cp <= 0x1F3FF):
            j = i + 1
            while j < n:
                c2 = ord(text[j])
                if c2 == 0xFE0F or 0x1F3FB <= c2 <= 0x1F3FF or 0xE0020 <= c2 <= 0xE007F:
                    j += 1
                    continue
                if c2 == 0x200D and j + 1 < n and is_emoji_cp(ord(text[j + 1])):
                    j += 2
                    continue
                break
            out.append(text[i:j].replace('️', ''))
            i = j
            continue
        i += 1
    return out


# ------------------------------------------------------- Burmese utilities
_CONS = 'က-အ'
_NO_MEDIAL_YA = 'င-နယရဝ-အ'   # consonants that never take medial ya (ျ)
_SYL_BREAK = re.compile('((?<!္)[' + _CONS + '](?!့?[်္])'
                        '|[a-zA-Z0-9ဣ-ဪဿ၀-၏!-/:-@\\[-`{-~\\s])')


def my_syllables(text):
    """Syllable segmentation (sylbreak rule; dot-below-before-asat tolerated). Unicode input only."""
    segs = _SYL_BREAK.sub(r' \1', text or '').split()
    return [s for s in segs if MY_RE.search(s)]


def zawgyi_detect(text):
    """Heuristic Zawgyi vs Unicode classifier for Burmese text.

    Zawgyi stores glyphs in VISUAL order and re-uses Myanmar code points, so a few
    sequences are impossible in correct Unicode (and vice versa). Signals:
      Zawgyi  Z1 ေ (U+1031) not preceded by a consonant/medial (typed before the consonant)
              Z2 U+103B before a consonant (Zawgyi medial-ra typed first; Unicode U+103B follows one)
              Z3 any U+1060-U+1097 (Zawgyi stacked/variant glyphs; Shan/Mon use them too — see limits)
              Z4 U+1039 not followed by a consonant (Zawgyi asat; Unicode U+1039 only stacks)
              Z5 U+1004/U+101A directly + U+1039 (asat on nga/ya in Zawgyi; Unicode needs U+103A)
              Z6 U+1033/U+1034 (Zawgyi long-leg u/uu; Mon vowels in Unicode)
      Unicode U1 kinzi U+1004 U+103A U+1039 (weight 2)
              U2 U+103A after a consonant that never takes medial ya (in Zawgyi U+103A IS medial ya)
              U3 U+1037 adjacent to U+103A (dot-below/asat pair; Zawgyi would use U+1039)
              U4 medial (U+103B-U+103E) then ေ (logical order; weight 0.5)
    Returns {'label': unicode|zawgyi|mixed|uncertain|none, 'z': {...}, 'u': {...}, 'p_zawgyi': float|None}.
    """
    text = text or ''
    my_n = len(MY_RE.findall(text))
    z = collections.Counter()
    u = collections.Counter()
    for m in re.finditer('ေ', text):
        prev = text[m.start() - 1] if m.start() else ' '
        if not re.match('[' + _CONS + 'ျ-ဿၐ-႟]', prev):
            z['Z1_e_before_consonant'] += 1
    z['Z2_medial_ra_first'] = len(re.findall('(?<![' + _CONS + '္])ျ(?=[' + _CONS + '])', text))
    z['Z3_zawgyi_glyph_range'] = len(re.findall('[ၠ-႗]', text))
    z['Z4_virama_as_asat'] = len(re.findall('္(?![' + _CONS + '])', text))
    z['Z5_nga_ya_virama'] = len(re.findall('[ငယ]္', text))
    z['Z6_long_leg_u'] = len(re.findall('[ဳဴ]', text))
    u['U1_kinzi'] = len(re.findall('င်္', text))
    u['U2_asat_no_medial_ya'] = len(re.findall('[' + _NO_MEDIAL_YA + ']့?်', text))
    u['U3_dot_below_asat'] = len(re.findall('့်|့်', text))
    u['U4_medial_then_e'] = len(re.findall('[ျ-ှ]ေ', text))
    z = {k: v for k, v in z.items() if v}
    u = {k: v for k, v in u.items() if v}
    zs = sum(z.values())
    us = 2 * u.get('U1_kinzi', 0) + u.get('U2_asat_no_medial_ya', 0) + u.get('U3_dot_below_asat', 0) \
        + 0.5 * u.get('U4_medial_then_e', 0)
    if my_n < 4:
        return {'label': 'none', 'z': z, 'u': u, 'p_zawgyi': None, 'my_chars': my_n}
    if zs == 0 and us == 0:
        return {'label': 'uncertain', 'z': z, 'u': u, 'p_zawgyi': None, 'my_chars': my_n}
    p = zs / (zs + us)
    label = 'zawgyi' if p >= 0.8 else 'unicode' if p <= 0.2 else 'mixed'
    return {'label': label, 'z': z, 'u': u, 'p_zawgyi': round(p, 3), 'my_chars': my_n}


# ------------------------------------------------------------- lexicons
NE_STRONG = {'lagi', 'cha', 'chha', 'xa', 'chan', 'hola', 'garna', 'garnu', 'garnus', 'gareko', 'garera', 'garne',
             'huncha', 'hunchha', 'hunxa', 'hoina', 'matra', 'lai', 'bata', 'hajur', 'tapai', 'tapain', 'sasto',
             'ramro', 'kinnus', 'kinna', 'kinnu', 'mauka', 'faida', 'linu', 'linus', 'yoh', 'tyo', 'aba', 'aile',
             'ahile', 'chito', 'sabai', 'dherai', 'milxa', 'milcha', 'parcha', 'pathaunu', 'pathauna', 'pathaunus',
             'koi', 'jati', 'kati', 'kasto', 'kahile', 'ani', 'tara', 'pheri', 'feri', 'sathi', 'haru', 'koseli',
             'chaina', 'xaina', 'chhaina', 'bhayo', 'bhaeko', 'bhayeko', 'didi', 'dai', 'daju', 'bahini', 'aaunus',
             'aunus', 'hernus', 'sodhnus', 'bhannus', 'lekhnus', 'thap', 'ota', 'euta', 'eutai', 'kalo', 'rato',
             'khairo', 'kharani', 'phool', 'butta', 'naya', 'aayo', 'bhari', 'mai', 'garchhau', 'pathauchhau',
             'lagyo', 'paryo', 'chahiyo', 'bhane', 'khusi', 'khabar', 'parne', 'japani', 'sahit', 'kinda'}
NE_WEAK = {'ko', 'ma', 'ra', 'ho', 'ni', 'la', 'yo', 'pani', 'ta', 'nai', 'ka', 'ki', 'hai', 'ke'}
ROMAJI = {'onegaishimasu', 'onegaisamasu', 'onegai', 'arigatou', 'arigato', 'gozaimasu', 'kawaii', 'sugoi',
          'yoroshiku', 'konnichiwa', 'sumimasen', 'daibiki', 'takkyubin', 'yupack', 'nippori', 'hachioji',
          'shinokubo', 'okubo', 'ikebukuro', 'shinjuku', 'tokyo', 'osaka'}
EN_VERBS = {'is', 'are', 'was', 'were', 'be', 'get', 'gets', 'buy', 'order', 'message', 'ship', 'ships', 'deliver',
            'delivers', 'come', 'comes', 'came', 'choose', 'pick', 'send', 'check', 'see', 'love', 'have', 'has',
            'tap', 'ask', 'tell', 'want', 'need', 'make', 'makes', 'shine', 'available', 'back', 'arrived', 'go',
            'pay', 'dm', 'call', 'visit', 'grab', 'thank', 'thanks', 'like', 'liked', 'missed', 'sold'}

LEX = {
    'casual': {
        'my': ['ပါတယ်', 'တယ်', 'နော်', 'ရှင့်', 'ရှင်', 'ဗျာ', 'ဗျ', 'လေး', 'ပီး', 'မယ်', 'ဘူး', 'လား', 'တဲ့', 'လို့',
               'ရင်', 'ပဲ', 'နဲ့', 'ကြ'],
        'ne': [r'\bko lagi\b', r'\bxa\b', r'\bcha\b', r'\bchha\b', r'\bhola\b', r'\bgarnus\b', r'\bgarnu\b', r'\bhai\b',
               r'\bdai\b', r'\bdidi\b', r'\bsathi\b', r'\bhajur\b', 'हजुर', 'दिदी', 'दाइ', 'गर्नुस्', r'\bmatra\b'],
        'ja': ['♪', '！', 'ね。', 'よ。', 'ね！', 'よ！', 'かわいい', '可愛い', 'ぜひ', 'めっちゃ', '♡', '(笑)', 'ですね',
               'ました！', 'です！', 'くださいね', 'ますね', 'どうぞ'],
        'en': [r'\bdm\b', r'\bpls\b', r'\bplz\b', r'\bguys\b', r'\bhey\b', '!!', r'\bomg\b', r'\bgonna\b', r'\bwanna\b',
               r'\bquick one\b'],
    },
    'formal': {
        'my': ['သည်', '၏', '၍', 'မည်', 'သော', '၌', 'ထို', 'လူကြီးမင်း', 'နှင့်'],
        'ne': ['उपलब्ध', 'उत्कृष्ट', 'प्रियजन', 'हार्दिक', 'सादर', 'यथाशीघ्र', 'गुणस्तरीय', 'सम्पर्क गर्नुहोला', 'अवसर'],
        'ja': ['しております', 'ございます', 'いたします', 'させていただ', '申し上げ', '誠に', '賜', '承っております'],
        'en': [r'\bkindly\b', r'\besteemed\b', r'\bvalued customers?\b', r'\bpleased to\b', r'\bdo not hesitate\b',
               r'\bfeel free to\b', r'\bdelighted\b', r'\bat your convenience\b', r'\bwe offer\b'],
    },
    'praise': {
        'en': [r'\bexquisite\b', r'\btimeless\b', r'\belevate', r'\bstunning\b', r'\bvibrant\b', r'\bperfect for\b',
               r'\bperfect blend\b', r'\brich colou?rs?\b', r'\bcrafted\b', r'\belegan(t|ce)\b', r'\ba touch of\b',
               r'\bwhether you\b', r'\bdiscover\b', r'\bembrace\b', r'\bversatile\b', r'\bluxurious\b',
               r'\bmust-have\b', r'\bshine in every light\b', r'\bevery project special\b'],
        'ja': ['上質', '華やか', '魅力', '彩', '最適', '洗練', '唯一無二', '至高', '贅沢', 'こだわり', '映える', 'きらめく',
               '伝統の', '職人'],
        'my': ['အထူးကောင်းမွန်', 'ဆန်းသစ်', 'အရည်အသွေးမြင့်', 'ထူးခြား', 'ပြီးပြည့်စုံ', 'ခမ်းနား', 'အကောင်းဆုံး'],
        'ne': ['उत्तम', 'आकर्षक', 'अद्भुत', 'शानदार', r'\bbest quality\b'],
    },
    'rhetorical_q': [r'\blooking for\b', r'\bsearching for\b', r'\bever wondered\b', 'お探し', 'ませんか？', 'ရှာနေ.{0,20}လား',
                     'खोज्दै', r'\bkhojdai\b'],
    'typo': [r'\borginal\b', r'\bavilable\b', r'\bavailble\b', r'\bdelivary\b', r'\bquaility\b', r'\bswostikgaesha\b',
             'ပီး'],
}
ADDRESS = {
    'my': ['အစ်မ', 'ညီမ', 'အကို', 'အစ်ကို', 'မမ', 'ကိုကို', 'Customer', 'customer', 'ချစ်သူ', 'မိတ်ဆွေ', 'လူကြီးမင်း',
           'အားပေးသူ', 'ဝယ်သူ', 'သူတွေ', 'သူများ', 'ရှင့်', 'ရှင်', 'ခင်ဗျာ', 'ခင်ဗျ'],
    'ne': ['दिदी', 'दाजु', 'दाइ', 'बहिनी', 'हजुर', 'तपाईं', 'साथी', r'\bdidi\b', r'\bdai\b', r'\bdaju\b', r'\bbahini\b',
           r'\bhajur\b', r'\btapai\b', r'\bsathi\b'],
    'ja': ['お客様', '皆さん', '皆様', 'みなさん', 'みなさま', 'あなた', 'フォロワー'],
    'en': [r'\byou\b', r'\byou all\b', r'\bdear\b', r'\bvalued customers?\b', r'\bvaluable customers\b', r'\bguys\b',
           r'\beveryone\b', r'\bfriends\b', r'\bsis\b'],
}
CATS = {
    'selling_points': {
        'original_authentic': [r'\borig?inal\b', r'\borginal\b', r'\bauthentic\b', r'\bgenuine\b', 'असली', 'စစ်စစ်', '本物'],
        'japan_origin': [r'\bjapan(ese|i)?\b', r'\bmade ?in ?japan\b', 'जापानी', 'जापानीज', 'ဂျပန်', '日本'],
        'material': [r'\bcotton\b', r'\b100 ?%', r'\bhosonuno\b', 'ချည်', '綿', 'コットン'],
        'price_value': [r'\bcheap\b', r'\bsasto\b', r'\brate\b', r'\bmatra\b', r'\bfixed price\b', r'\b1 free\b',
                        r'\bget 1 free\b', 'ဈေးသက်သာ', 'सस्तो', 'お得', '無料'],
        'new_arrival': [r'\bnew ?arrivals?\b', r'\bnew\b', r'\bnaya\b', 'အသစ်', '新入荷', 'नयाँ'],
        'gift': [r'\bgift\b', r'\bkoseli\b', 'कोसेली', 'လက်ဆောင်', 'ギフト', 'プレゼント'],
        'festival': [r'\bdashain\b', r'\btihar\b', 'दशैं', 'तिहार', 'သင်္ကြန်', '正月'],
        'variety': [r'\bcolou?rs?\b', r'\bdesigns?\b', r'\bpatterns?\b', 'အရောင်', 'အဆင်', '色', '柄', 'रङ'],
        'scarcity': [r'\blimited\b', r'\bstock ?out\b', r'\bsold ?out\b', r'\bfew left\b', 'ပြတ်', '在庫限り', '残り', '完売',
                     'सकियो'],
        'quality': [r'\bquality\b', r'\bramro\b', 'राम्रो', '品質'],
        'beauty': [r'\bbeautiful\b', r'\bpretty\b', 'လှလှ', 'かわいい', 'きれい', '素敵'],
        'gratitude_trust': [r'\bthank', r'\blove and support\b', r'\bblessed\b', 'ယုံကြည်', 'ကျေးဇူး', 'ありがとう', 'धन्यवाद'],
        'location': [r'\bnippori\b', r'\bhachioji\b', '八王子', r'\btokyo\b', '東京'],
    },
    'price': {
        'yen_symbol': [r'[¥￥]\s?\d'],
        'yen_word': [r'\d\s?(円|yen\b|ယန်း)'],
        'only_word': [r'\bmatra\b', r'\bonly\b', 'だけ', 'मात्र', r'\bpe?\b'],
        'fixed_price': [r'\bfixed price\b', r'\bno bargaining\b', 'ဈေးမဆစ်', '定価'],
        'tax': [r'\btax\b', '税込'],
        'bundle_offer': [r'\bbuy 11\b', r'\b11 ?for ?10\b', '11枚'],
        'price_hidden': [r'\bprice in (inbox|dm)\b', r'\bpm for price\b', 'ဈေးနှုန်း.{0,10}inbox'],
    },
    'cta': {
        'inbox_dm': [r'\binbox\b', r'\bdm\b', r'\bmessage us\b', r'\bmsg\b', 'メッセージ', 'मेसेज', 'म्यासेज', 'इनबक्स',
                     'စာပို့', 'message ပို့'],
        'messenger_link': [r'm\.me/'],
        'messenger_word': [r'\bmessenger\b'],
        'social_handles': [r'\bfb\s*:', r'\binsta\b', r'\binstagram\b', r'\bfacebook\b'],
        'comment': [r'\bcomment\b', 'コメント', 'कमेन्ट'],
        'call': [r'\bcall\b', r'\bphone\b', '☎', '📞', 'ဖုန်း', 'फोन', '電話'],
        'order_word': [r'\border\b', r'\bplace ?order', 'မှာ', '注文', 'अर्डर'],
        'visit_store': [r'\bvisit\b', r'\bpick ?up\b', '店頭', 'ဆိုင်'],
    },
    'delivery': {
        'nationwide': [r'\ball over japan\b', r'\bacross japan\b', r'\banywhere in japan\b', r'\bjapan bhari\b', '全国',
                       'ဂျပန်ပြည်တွင်း', 'ဂျပန်တစ်နိုင်ငံလုံး', 'ဂျပန်နိုင်ငံ ဘယ်နေရာ', 'जापानभरि'],
        'cod': [r'\bcod\b', r'\bcash on delivery\b', '代引', '代金引換', 'ငွေချေ', r'\bdaibiki\b'],
        'delivery_word': [r'\bdeliver', r'\bship', '発送', '配送', 'ပို့', 'डेलिभरी', 'पठाउ'],
        'payment_methods': [r'\bpaypay\b', r'\bcredit card\b', r'\bcard\b', r'\bbank\b', 'クレジット', '銀行', 'ဘဏ်'],
    },
    'stock': {
        'available': [r'\bavailable\b', r'\bin ?stock\b', r'\bstock ma\b', 'ရနိုင်', 'ရှိပါတယ်', '在庫あり', '在庫あります'],
        'sold_out': [r'\bsold ?out\b', r'\bstock ?out\b', 'ပြတ်', '完売', '売り切れ', 'सकियो'],
        'restock': [r'\brestock', r'\bback in stock\b', r'\bfeli? aayo\b', r'\bferi aayo\b', 'ပြန်ရောက်', '再入荷', 'फेरि'],
        'new_arrival': [r'\bnew ?arrivals?\b', '新入荷', 'အသစ်'],
    },
}


def _count(patterns, text):
    hits = collections.Counter()
    for p in patterns:
        n = len(re.findall(p, text, re.I))
        if n:
            hits[p if not p.startswith('\\b') else p.replace('\\b', '')] += n
    return hits


def _cats(group, text):
    out = {}
    for cat, pats in CATS[group].items():
        found = _count(pats, text)
        if found:
            out[cat] = sorted(found)[:3]
    return out


# ------------------------------------------------------------ text features
def split_body(text):
    """Body = text without URLs, @mentions and hashtags (hashtags returned separately)."""
    t = text or ''
    tags = ['#' + h.rstrip('.,!?') for h in HASHTAG_RE.findall(t)]
    body = URL_RE.sub(' ', t)
    body = MENTION_RE.sub(' ', body)
    body = HASHTAG_RE.sub(' ', body)
    body = re.sub(r'(?<!\S)[A-Za-z0-9_]+\.[A-Za-z0-9_.]*[A-Za-z0-9_](?!\S)', ' ', body)   # dotted account names
    return body, tags


def script_counts(s):
    return {'my': len(MY_RE.findall(s)), 'ne_dev': len(DEV_RE.findall(s)), 'ja': len(JA_RE.findall(s)),
            'latin': len(LAT_RE.findall(s)), 'fancy': len(FANCY_RE.findall(s))}


def tokens_latin(s):
    s = CODE_RE.sub(' ', s)
    return [w.lower().strip("'’") for w in WORD_RE.findall(s) if len(w) >= 2 or w.lower() in ('a', 'i')]


def detect_language(body, tags, hint=None):
    sc = script_counts(body)
    toks = tokens_latin(body)
    strong = [t for t in toks if t in NE_STRONG]
    weak = [t for t in toks if t in NE_WEAK]
    ne_roman = len(set(strong)) >= 2 or (len(strong) >= 1 and len(weak) >= 1)
    letters = {'my': sc['my'], 'ne': sc['ne_dev'], 'ja': sc['ja'], 'latin': sc['latin'] + sc['fancy']}
    if sum(letters.values()) == 0:
        tsc = script_counts(' '.join(tags))
        if not tags:
            return (hint or 'none'), True, ne_roman
        best = max(('my', tsc['my']), ('ne', tsc['ne_dev']), ('ja', tsc['ja']), ('en', tsc['latin']), key=lambda x: x[1])[0]
        return (hint or best), True, ne_roman
    best = max(letters.items(), key=lambda x: x[1])[0]
    if best == 'latin':
        best = 'ne' if ne_roman else 'en'
    return (hint or best), False, ne_roman


def first_line(body_text):
    for line in (body_text or '').splitlines():
        if line.strip():
            return line.strip()
    return ''


def hook_types(text, body):
    line = first_line(body)
    low = line.lower()
    types = []
    if not line:
        return ['hashtags_only']
    if (text or '').lstrip().lower().startswith('replying to'):
        types.append('reply_to_comment')
    if re.search(r'thank|धन्यवाद|ကျေးဇူး|ありがとう', low):
        types.append('gratitude')
    if re.search(r'namaste|नमस्ते|မင်္ဂလာပါ|こんにちは|\bhello\b|\bhi\b', low):
        types.append('greeting')
    if re.search(r'new ?arrival|\bnew\b|\bnaya\b|အသစ်|入荷|नयाँ', low):
        types.append('announcement_new')
    if re.search(r'restock|back in stock|feri aayo|ပြန်ရောက်|再入荷|फेरि आयो', low):
        types.append('restock')
    if re.search(r'available|in ?stock|stock ma|ရပါပြီ|在庫|ရှိပါတယ်', low):
        types.append('availability')
    if re.search(r'deliver|ship|ပို့|発送|डेलिभरी', low):
        types.append('delivery')
    if PRICE_RE.search(line):
        types.append('price_in_first_line')
    if re.search(r'nippori|hachioji|八王子|tokyo|東京', low):
        types.append('location')
    if re.search(r'[?？]', line) or re.search('|'.join(LEX['rhetorical_q']), low):
        types.append('question')
    if FANCY_RE.search(line) or _symmetric_emoji(line):
        types.append('title_banner')
    if re.search(r'lungi|longyi|kimono|fabric|生地|和柄|လုံချည်|ကီမိုနို|लुंगी|लुङ्गी', low):
        types.append('product_named')
    return types or ['other']


def _symmetric_emoji(line):
    em = extract_emoji(line)
    s = line.strip()
    if len(em) < 2 or not s:
        return False
    lead = extract_emoji(s[:3])
    tail = extract_emoji(s[-3:])
    return bool(lead and tail and lead[0] == tail[-1])


def _is_telegraphic_ja(line):
    core = URL_RE.sub('', line)
    core = ''.join(ch for ch in core if not is_emoji_cp(ord(ch)) and not (0x1F1E6 <= ord(ch) <= 0x1F1FF)).strip()
    if len(JA_RE.findall(core)) < 6:
        return False
    if re.search(r'(です|ます|した|ません|でした|ください|どうぞ|ね|よ|な|か|う|る|い|た|だ)[。！!♪)）]*$', core):
        return False
    return True


def _is_telegraphic_en(line):
    toks = tokens_latin(URL_RE.sub(' ', line))
    if len(toks) < 4:
        return False
    return not any(t in EN_VERBS for t in toks)


def analyse_text(text, lang_hint=None):
    text = (text or '').replace('\r\n', '\n')
    body, tags = split_body(text)
    lang, tags_only, ne_roman = detect_language(body, tags, lang_hint)
    sc_body = script_counts(body)
    sc_tags = script_counts(' '.join(tags))
    lines = [ln for ln in text.splitlines() if ln.strip()]
    toks = tokens_latin(body)
    ne_tok = [t for t in toks if t in NE_STRONG or (ne_roman and t in NE_WEAK)]
    romaji = [t for t in toks if t in ROMAJI]
    en_tok = [t for t in toks if t not in ROMAJI and not (ne_roman and (t in NE_STRONG or t in NE_WEAK))]
    syl = my_syllables(body) if sc_body['my'] else []
    dev_tok = re.findall(r'[ऀ-ॿ]+', body)
    ja_units = len(JA_RE.findall(body)) / 2.0
    units = len(en_tok) + len(ne_tok) + len(romaji) + len(syl) + len(dev_tok) + ja_units
    ws_words = [w for w in body.split() if re.search(r'[A-Za-zÀ-ɏ' + MY_CHARS + 'ऀ-ॿ' + JA_CHARS + r'\d]', w)]
    emoji = extract_emoji(text)
    zg = zawgyi_detect(body) if sc_body['my'] else None

    if lang == 'my':
        script = {'unicode': 'my_unicode', 'zawgyi': 'my_zawgyi'}.get((zg or {}).get('label'), 'mixed')
    elif lang == 'ne':
        script = 'ne_devanagari' if sc_body['ne_dev'] >= sc_body['latin'] else 'ne_roman'
    elif lang in ('ja', 'en'):
        script = lang
    else:
        script = None
    letters_total = sum(v for k, v in sc_body.items() if k != 'fancy') or 1
    dominant = max(('my', 'ne_dev', 'ja', 'latin'), key=lambda k: sc_body[k])
    second = sorted((sc_body[k] for k in ('my', 'ne_dev', 'ja', 'latin')), reverse=True)[1]
    if lang in ('my', 'ja') and second / letters_total >= 0.35 and dominant != 'latin':
        script = 'mixed'

    l_low = text.lower()
    casual = _count(LEX['casual'].get(lang, []), text if lang in ('my', 'ja') else l_low)
    formal = _count(LEX['formal'].get(lang, []), text if lang in ('my', 'ja', 'ne') else l_low)
    praise = _count(LEX['praise'].get(lang, []), text if lang in ('my', 'ja', 'ne') else l_low)
    ja_lines = [ln for ln in lines if JA_RE.search(ln)]
    en_lines = [ln for ln in lines if LAT_RE.search(ln) and not MY_RE.search(ln) and not DEV_RE.search(ln)
                and not JA_RE.search(ln) and not ln.strip().startswith('#')]
    tele_ja = sum(_is_telegraphic_ja(ln) for ln in ja_lines)
    tele_en = sum(_is_telegraphic_en(ln) for ln in en_lines)
    telegraphic = (lang == 'ja' and ja_lines and tele_ja / len(ja_lines) >= 0.5) or \
                  (lang == 'en' and en_lines and tele_en / len(en_lines) >= 0.5 and len(toks) >= 4)
    n_cas, n_for = sum(casual.values()), sum(formal.values())
    tone = 'telegraphic' if telegraphic and n_cas == 0 else 'formal' if n_for > n_cas else \
        'casual' if n_cas > 0 else 'neutral'
    addr = _count(ADDRESS.get(lang, []), text if lang in ('my', 'ja', 'ne') else l_low)

    feats = {
        'hashtags_only': tags_only,
        'first_line_hook': hook_types(text, body),
        'address_terms': dict(addr),
        'casual_markers': dict(casual),
        'formal_markers': dict(formal),
        'praise_words': dict(praise),
        'selling_points': _cats('selling_points', text),
        'price': _cats('price', text),
        'cta': _cats('cta', text),
        'delivery': _cats('delivery', text),
        'stock': _cats('stock', text),
        'em_dash': len(re.findall('[—–]', text)),
        'middle_dot_lines': sum(1 for ln in lines if len(re.findall(' · ', ln)) >= 2
                                or (len(MID_DOT_RE.findall(ln.lstrip('・'))) >= 2 and _is_telegraphic_ja(ln))),
        'flag_label_lines': len(FLAG_LINE_RE.findall(text)),
        'symmetric_emoji_lines': sum(1 for ln in lines if _symmetric_emoji(ln)),
        'emoji_led_lines': sum(1 for ln in lines if extract_emoji(ln.strip()[:2])),
        'fancy_font_chars': sc_body['fancy'],
        'telegraphic_lines': {'ja': tele_ja, 'en': tele_en},
        'decor_symbols': ''.join(ch for ch in text if ch in DECOR),
        'romanized_nepali_tokens': sorted(set(ne_tok))[:20],
        'romaji_tokens': sorted(set(romaji)),
        'typos': sorted(_count(LEX['typo'], text if lang == 'my' else l_low)),
        'rhetorical_question': bool(re.search('|'.join(LEX['rhetorical_q']), text if lang in ('my', 'ja', 'ne') else l_low)),
        'code_mix_latin_words': len(en_tok) if lang in ('my', 'ne', 'ja') else 0,
        'spacing_quirks': bool(re.search(r'  +\S|\( | \)|\bjapan\b', text)),
    }
    detail = {
        'body_chars': sc_body, 'hashtag_chars': sc_tags,
        'zawgyi': zg, 'ne_romanized': ne_roman,
        'tokens': {'en': len(en_tok), 'ne_roman': len(ne_tok), 'romaji': len(romaji), 'my_syllables': len(syl),
                   'devanagari': len(dev_tok), 'ja_chars': int(ja_units * 2)},
    }
    return {
        'language': lang, 'script': script, 'script_detail': detail,
        'word_count': len(ws_words), 'unit_count': int(round(units)), 'line_count': len(lines),
        'char_count': len(text), 'emoji_count': len(emoji),
        'emoji_list': dict(collections.Counter(emoji).most_common()),
        'hashtag_count': len(tags), 'hashtags': tags,
        'english_mix_ratio': round(len(en_tok) / units, 3) if units else None,
        'tone': tone, 'features': feats,
    }


# ------------------------------------------------------------ AI rubric
WEIGHTS = {
    'S1_em_dash': 0.20, 'S2_middle_dot_chain': 0.15, 'S3_telegraphic': 0.15, 'S4_formal_register': 0.25,
    'S5_generic_praise_1': 0.10, 'S5_generic_praise_2plus': 0.20, 'S6a_symmetric_emoji_frame': 0.10,
    'S6b_emoji_bullets': 0.10, 'S6c_flag_language_labels': 0.10, 'S7_parallel_translation': 0.10,
    'S8_template_repetition': 0.10, 'S9_rhetorical_question_hook': 0.10, 'S11_ai_image_suspected': 0.60,
    'S12_devanagari_body_vs_measured': 0.10,
    'H1_informal_spelling_or_grammar': -0.15, 'H2_colloquial_1_2': -0.10, 'H2_colloquial_3plus': -0.20,
    'H3_natural_code_mixing': -0.10, 'H4_short_direct': -0.05, 'H5_spacing_quirks': -0.05,
    'H6_specific_local_detail': -0.05,
}


def ai_rubric(a, ctx=None):
    """Score 0..1 from explicit, documented signals. ctx: ai_image_suspected, parallel_translation,
    repetition_ratio (share of the text's lines that recur in >=80% of posts), devanagari_body_evidence,
    human_markers (analyst annotations)."""
    ctx = ctx or {}
    f = a['features']
    lang = a['language']
    sig, hum, notes = [], [], []

    def add(code, detail):
        (sig if WEIGHTS[code] > 0 else hum).append({'code': code, 'weight': WEIGHTS[code], 'detail': detail})

    if f['em_dash']:
        add('S1_em_dash', f"{f['em_dash']} em/en dash(es); measured human captions use none")
    if f['middle_dot_lines']:
        add('S2_middle_dot_chain', f"{f['middle_dot_lines']} line(s) chaining fragments with · / ・")
    if a['tone'] == 'telegraphic':
        add('S3_telegraphic', 'noun-stack lines without a verb/predicate (catalog voice)')
    if f['formal_markers'] and not f['casual_markers']:
        add('S4_formal_register', 'formal/literary markers ' + ', '.join(list(f['formal_markers'])[:4]) + ', no colloquial ones')
    n_pr = sum(f['praise_words'].values())
    if n_pr >= 2:
        add('S5_generic_praise_2plus', 'generic praise: ' + ', '.join(list(f['praise_words'])[:4]))
    elif n_pr == 1:
        add('S5_generic_praise_1', 'generic praise: ' + ', '.join(f['praise_words']))
    if f['symmetric_emoji_lines']:
        add('S6a_symmetric_emoji_frame', f"{f['symmetric_emoji_lines']} line(s) framed by the same emoji (🎀 … 🎀)")
    elif ctx.get('symmetric_header'):
        add('S6a_symmetric_emoji_frame', 'post header framed by the same emoji (frame split across language blocks)')
    if a['line_count'] >= 3 and f['emoji_led_lines'] >= 3 and f['emoji_led_lines'] / a['line_count'] >= 0.6:
        add('S6b_emoji_bullets', f"{f['emoji_led_lines']}/{a['line_count']} lines start with an emoji")
    if f['flag_label_lines']:
        add('S6c_flag_language_labels', f"{f['flag_label_lines']} line(s) labelled with a flag (🇯🇵/🇬🇧/🇳🇵 translation blocks)")
    if ctx.get('parallel_translation'):
        add('S7_parallel_translation', 'same sentence repeated as a translation in another language block')
    if ctx.get('repetition_ratio', 0) >= 0.5:
        add('S8_template_repetition', f"{int(ctx['repetition_ratio'] * 100)}% of its lines recur verbatim in >=80% of posts")
    if f['rhetorical_question']:
        add('S9_rhetorical_question_hook', 'marketing-style rhetorical question')
    if ctx.get('ai_image_suspected'):
        add('S11_ai_image_suspected', 'image flagged as AI-generated in fi_competitor_posts')
    if lang == 'ne' and ctx.get('devanagari_body_evidence') and a['script_detail']['tokens']['devanagari'] >= 3:
        add('S12_devanagari_body_vs_measured', ctx['devanagari_body_evidence'])

    if f['typos'] or ctx.get('human_markers'):
        add('H1_informal_spelling_or_grammar', ', '.join(f['typos'] + list(ctx.get('human_markers') or []))[:120])
    n_cas = sum(f['casual_markers'].values())
    if n_cas >= 3:
        add('H2_colloquial_3plus', 'colloquial markers: ' + ', '.join(list(f['casual_markers'])[:5]))
    elif n_cas >= 1:
        add('H2_colloquial_1_2', 'colloquial markers: ' + ', '.join(f['casual_markers']))
    if f['code_mix_latin_words'] >= 2 and lang in ('my', 'ne', 'ja'):
        add('H3_natural_code_mixing', f"{f['code_mix_latin_words']} English words inside the {lang} text")
    if 0 < a['word_count'] <= 12 and not n_pr and not f['em_dash'] and not f['middle_dot_lines']:
        add('H4_short_direct', f"{a['word_count']} words, no filler")
    if f['spacing_quirks']:
        add('H5_spacing_quirks', 'irregular spacing / lowercase place names (typed by hand)')
    if 'location' in f['selling_points'] or 'festival' in f['selling_points'] or 'reply_to_comment' in f['first_line_hook']:
        add('H6_specific_local_detail', 'place/festival/reply detail')

    if f['fancy_font_chars']:
        notes.append('fancy Unicode font (𝒩𝐸𝒲): common among Myanmar FB sellers but invisible to search and screen readers')
    if f['hashtags_only']:
        notes.append('no body text, hashtags only')

    raw = sum(s['weight'] for s in sig) + sum(h['weight'] for h in hum)
    score = round(min(1.0, max(0.0, raw)), 3)
    return score, sig, hum, notes


# ------------------------------------------------------------ copy guard
def _norm_words(text):
    t = URL_RE.sub(' xurlx ', text or '')
    t = CODE_RE.sub(' xcodex ', t)
    t = re.sub(r'[¥￥]\s?[\d,]+|[\d,]+\s?円', ' xpricex ', t).lower()
    return re.findall(r"[a-zÀ-ɏ]+(?:['’][a-z]+)?|[ऀ-ॿ]+|\d+", t)


def _runs(text, charclass, minlen):
    out = []
    for r in re.findall('[' + charclass + r'\s​]+', text or ''):
        r = re.sub(r'[\s​]', '', r)
        if len(r) >= minlen:
            out.append(r)
    return out


class CopyGuard:
    """No shared run of >=6 words, >=20 Burmese chars or >=12 Japanese chars with any corpus text."""

    def __init__(self, corpus, words=6, my_chars=20, ja_chars=12):
        self.n, self.mc, self.jc = words, my_chars, ja_chars
        self.word_idx, self.my_idx, self.ja_idx = {}, {}, {}
        for ref, text in corpus:
            w = _norm_words(text)
            for i in range(len(w) - words + 1):
                self.word_idx.setdefault(tuple(w[i:i + words]), ref)
            for run in _runs(text, MY_CHARS, my_chars):
                for i in range(len(run) - my_chars + 1):
                    self.my_idx.setdefault(run[i:i + my_chars], ref)
            for run in _runs(text, JA_CHARS, ja_chars):
                for i in range(len(run) - ja_chars + 1):
                    self.ja_idx.setdefault(run[i:i + ja_chars], ref)
        self.size = len(corpus)

    def check(self, text):
        hits = []
        w = _norm_words(text)
        for i in range(len(w) - self.n + 1):
            ref = self.word_idx.get(tuple(w[i:i + self.n]))
            if ref is not None:
                hits.append({'type': 'words', 'ref': ref, 'run': ' '.join(w[i:i + self.n])})
        for run in _runs(text, MY_CHARS, self.mc):
            for i in range(len(run) - self.mc + 1):
                ref = self.my_idx.get(run[i:i + self.mc])
                if ref is not None:
                    hits.append({'type': 'burmese_chars', 'ref': ref, 'run_len': self.mc})
                    break
        for run in _runs(text, JA_CHARS, self.jc):
            for i in range(len(run) - self.jc + 1):
                ref = self.ja_idx.get(run[i:i + self.jc])
                if ref is not None:
                    hits.append({'type': 'japanese_chars', 'ref': ref, 'run_len': self.jc})
                    break
        uniq = []
        for h in hits:
            if h not in uniq:
                uniq.append(h)
        return {'passed': not uniq, 'against_texts': self.size, 'hits': uniq[:5],
                'rule': f'>={self.n} words / >={self.mc} Burmese chars / >={self.jc} Japanese chars'}


# ------------------------------------------------------------ fact guard (drafts)
LEN_RE = re.compile(r'\d+(?:\.\d+)?\s?(?:cm|mm|m|meter|metre|メートル|センチ|ｍ|မီတာ|मिटर|मि\.)(?![a-z])|幅|丈|長さ|width|length|\bwide\b',
                    re.I)


def fact_check(text, product, lang, sold_out):
    problems = []
    body = text or ''
    if '細布' in body:
        problems.append('contains 細布')
    if LEN_RE.search(URL_RE.sub(' ', body)):
        problems.append('width/length wording: ' + LEN_RE.search(URL_RE.sub(' ', body)).group(0))
    if SUPPLIER_CODE_RE.search(body) or re.search(r'naka', body, re.I):
        problems.append('supplier code / supplier name')
    prices = re.findall(r'[¥￥]\s?([\d,]+)|([\d,]+)\s?円', body)
    for a, b in prices:
        v = (a or b).replace(',', '')
        if v != '1595':
            problems.append(f'price {v} is not the catalog price 1595')
    if MESSENGER not in body:
        problems.append('Messenger link missing')
    if sold_out and not body.lstrip().startswith('[' + DNP + ']'):
        problems.append('sold-out draft must start with [' + DNP + ']')
    if not sold_out and DNP in body:
        problems.append('in-stock draft carries the restock marker')
    if lang == 'ja' and ('コットン' in body or 'cotton' in body.lower()):
        problems.append('JA material must be 綿100% only')
    if lang == 'en' and re.search(r'cotton', body, re.I) and 'Hosonuno (100% cotton)' not in body:
        problems.append('EN material must read "Hosonuno (100% cotton)"')
    if lang != 'ja' and '綿' in body:
        problems.append('綿 outside Japanese text')
    if lang == 'my':
        z = zawgyi_detect(body)
        if z['label'] not in ('unicode',):
            problems.append('Burmese not confirmed Unicode: ' + z['label'])
    codes = set(re.findall(r'\bFV-\d+-[A-Z]\b', body))
    allowed = set(product.get('codes', []))
    for c in codes:
        if allowed and c not in allowed:
            problems.append(f'code {c} not in this draft group')
    return problems


# ------------------------------------------------------------ helpers
def fp(*parts):
    return hashlib.sha256('|'.join(str(p) for p in parts).encode('utf-8')).hexdigest()


def med(xs):
    xs = [x for x in xs if x is not None]
    return statistics.median(xs) if xs else None


def rng(xs):
    xs = [x for x in xs if x is not None]
    return [min(xs), max(xs)] if xs else None


def clean_overlay(s):
    if not s:
        return None
    s = re.sub(r'\[[^\]]*\]', ' ', s)
    s = re.sub(r"'[^']*' fragment at crop edge", ' ', s)
    s = ' / '.join(p.strip() for p in s.split('/') if p.strip())
    return s or None


def write_text(path, content):
    os.makedirs(os.path.dirname(os.path.abspath(path)), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as fh:
        fh.write(content)
    if os.path.getsize(path) == 0:
        raise SystemExit(f'zero-size output: {path}')


# ------------------------------------------------------------ inbox
IMG_EXT = ('.png', '.jpg', '.jpeg', '.webp')
FNAME_RE = re.compile(r'^(?P<pf>fb|ig|tt|other)_(?P<shop>.+?)_(?P<date>\d{8})_(?P<n>\d+)(?P<part>[a-z])?$', re.I)
PLATFORM = {'fb': 'facebook', 'ig': 'instagram', 'tt': 'tiktok', 'other': None}


def _read_sidecar(path):
    meta, lines = {}, []
    with open(path, encoding='utf-8', errors='replace') as fh:
        for ln in fh:
            m = re.match(r'^#\s*(source|confidence|verified|notes|handle|platform)\s*:\s*(.*)$', ln.strip(), re.I)
            if m and not lines:
                meta[m.group(1).lower()] = m.group(2).strip()
            else:
                lines.append(ln.rstrip('\n'))
    return meta, '\n'.join(lines).strip()


def redact(text):
    t = PHONE_RE.sub('[phone_redacted]', text or '')
    return MENTION_RE.sub('@[mention_redacted]', t)


def cmd_inbox(args):
    base = args.dir
    items, pending, ocr_ready = [], [], False
    groups = collections.OrderedDict()
    for lang in LANGS:
        d = os.path.join(base, lang)
        if not os.path.isdir(d):
            continue
        for name in sorted(os.listdir(d)):
            path = os.path.join(d, name)
            if not os.path.isfile(path) or name.lower().endswith(('.claude.txt', '.ocr.txt')) or name == 'README.txt':
                continue
            stem, ext = os.path.splitext(name)
            ext = ext.lower()
            m = FNAME_RE.match(stem)
            key = (lang, (m.group('pf') + '_' + m.group('shop') + '_' + m.group('date') + '_' + m.group('n')) if m else stem)
            groups.setdefault(key, []).append((path, ext, m))
    for (lang, key), parts in groups.items():
        texts, sources, confs, verified, files = [], [], [], True, []
        m = parts[0][2]
        for path, ext, _ in sorted(parts):
            files.append(os.path.relpath(path, base))
            if ext == '.txt':
                meta, txt = _read_sidecar(path)
                texts.append(txt)
                sources.append(meta.get('source', 'owner_paste'))
                confs.append(float(meta.get('confidence', 1.0)))
                continue
            if ext not in IMG_EXT:
                continue
            claude, ocr = path + '.claude.txt', path + '.ocr.txt'
            if os.path.exists(claude):
                meta, txt = _read_sidecar(claude)
                texts.append(txt)
                sources.append('claude_read')
                confs.append(float(meta.get('confidence', 0.7)))
            elif lang in ('en', 'ja'):
                if not os.path.exists(ocr) and args.ocr:
                    if not ocr_ready:
                        sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
                        sys.path.insert(0, HEAVY_DIR)
                        __import__('heavy_guard').acquire('caption_style_inbox')   # one heavy job at a time + memory cap
                        ocr_ready = True
                    tl = 'eng' if lang == 'en' else 'jpn+eng'
                    r = subprocess.run(['nice', '-n', '10', 'tesseract', path, 'stdout', '-l', tl, '--psm', '4'],
                                       capture_output=True, text=True, timeout=180)
                    write_text(ocr, '# source: tesseract_ocr\n# confidence: 0.6\n# verified: no\n' + r.stdout)
                if os.path.exists(ocr):
                    meta, txt = _read_sidecar(ocr)
                    texts.append(txt)
                    sources.append('tesseract_ocr')
                    confs.append(float(meta.get('confidence', 0.6)))
                    verified = verified and meta.get('verified', 'no').lower() == 'yes'
                else:
                    pending.append({'files': files, 'lang': lang, 'need': 'tesseract (run inbox --ocr)'})
                    texts = []
                    break
            else:
                pending.append({'files': files, 'lang': lang, 'need': 'claude_read: write <image>.claude.txt'})
                texts = []
                break
        if not texts:
            continue
        src = 'claude_read' if 'claude_read' in sources else 'tesseract_ocr' if 'tesseract_ocr' in sources else 'owner_paste'
        items.append({
            'key': key, 'lang': lang, 'files': files, 'source': src,
            'text': redact('\n'.join(t for t in texts if t)), 'confidence': round(min(confs), 3) if confs else None,
            'verified': verified, 'platform': PLATFORM.get((m.group('pf') if m else 'other').lower()),
            'handle': m.group('shop') if m else None, 'date': m.group('date') if m else None,
        })
    out = {'generated_at': datetime.now(timezone.utc).isoformat(), 'dir': base, 'items': items, 'pending': pending}
    write_text(args.out, json.dumps(out, ensure_ascii=False, indent=1))
    print(f'inbox: {len(items)} item(s), {len(pending)} pending -> {args.out}')


# ------------------------------------------------------------ v1 caption segmentation
V1_COLOURS = {
    'black': ('Black', '黒', 'kalo'), 'white': ('White', '白', 'seto'), 'grey': ('Grey', 'グレー', 'kharani'),
    'red': ('Red', '赤', 'rato'), 'orange': ('Orange', 'オレンジ', 'suntala'), 'gold': ('Gold', '金・黄', 'sunaulo'),
    'brown': ('Brown', '茶', 'khairo'), 'green': ('Green', '緑', 'hariyo'), 'blue': ('Blue', '青', 'nilo'),
    'navy': ('Navy', '紺', 'gadha nilo'), 'purple': ('Purple', '紫', 'bainjani'), 'pink': ('Pink', 'ピンク', 'gulabi'),
}


def segment_v1(caption):
    """Split a v1 collection caption into language segments. Burmese lines keep their English code-mixing;
    a ' · ' separator splits mixed-language lines (header, CTA); Devanagari/Myanmar runs on one line are split."""
    seg = collections.OrderedDict((k, []) for k in ('my', 'ne', 'ja', 'en', 'shared'))
    my_para_lines = set()
    for para in re.split(r'\n\s*\n', caption):
        plines = [x.strip() for x in para.splitlines() if x.strip()]
        if any(MY_RE.search(x) and not DEV_RE.search(x) and ' · ' not in x for x in plines):
            my_para_lines.update(x for x in plines if not URL_RE.search(x) and not JA_RE.search(x)
                                 and not DEV_RE.search(x) and LAT_RE.search(CODE_RE.sub('', x)))
    for raw in caption.splitlines():
        line = raw.strip()
        if not line:
            continue
        if line.startswith('#'):
            seg['shared'].append(line)
            continue
        if line in my_para_lines and not MY_RE.search(line):
            seg['my'].append(line)      # English words typed inside a Burmese paragraph ("New arrival")
            continue
        parts = [line]
        if ' · ' in line and (MY_RE.search(line) or JA_RE.search(line)):
            parts = [p.strip() for p in line.split(' · ')]
        elif MY_RE.search(line) and DEV_RE.search(line):
            idx = MY_RE.search(line).start()
            parts = [line[:idx].strip(), line[idx:].strip()]
        for p in parts:
            if not p:
                continue
            core = URL_RE.sub('', CODE_RE.sub('', p))
            if MY_RE.search(p):
                seg['my'].append(p)
            elif DEV_RE.search(p):
                seg['ne'].append(p)
            elif JA_RE.search(p):
                seg['ja'].append(p)
            elif not LAT_RE.search(re.sub(r'\b[A-Z]\b|\b\d+m\b', '', core)) and not FANCY_RE.search(core):
                seg['shared'].append(p)     # codes, prices, bare link
            else:
                seg['en'].append(p)
    return {k: '\n'.join(v) for k, v in seg.items() if v}


def _mask(line):
    t = CODE_RE.sub('<code>', line)
    t = re.sub(r'\d[\d,]*', '<n>', t)
    for en, ja, _ in V1_COLOURS.values():
        t = t.replace(en, '<colour>').replace(ja, '<colour>')
    t = re.sub('|'.join(['အနက်ရောင်', 'အဖြူရောင်', 'မီးခိုးရောင်', 'အနီရောင်', 'လိမ္မော်ရောင်', 'ရွှေဝါရောင်', 'အညိုရောင်',
                         'အစိမ်းရောင်', 'အပြာရောင်', 'အပြာရင့်ရောင်', 'ခရမ်းရောင်', 'ပန်းရောင်']), '<colour>', t)
    return t


def v1_facts(row):
    """Facts stated in the PUBLISHED caption text of one queue row (caption_meta only to expand
    'FU-62 [C · D]' into full codes). caption_meta.codes can list products that were not shown, so it is
    never used on its own."""
    cap = row['caption']
    meta = row.get('caption_meta') or {}
    body = '\n'.join(ln for ln in cap.splitlines() if not ln.strip().startswith('#'))
    m = re.search(r'🔖\s*(.+?)\s+[—–]\s+Sold out', cap)
    sold = [c.strip() for c in m.group(1).split('/')] if m else []
    colour = None
    mc = re.search(r'🇬🇧\s*([A-Z][a-z]+) background', cap) or re.search(r'^\W*\s*([A-Z][a-z]+) (?:Collection|Kimono Lungi|Background)', cap, re.M)
    if mc and mc.group(1).lower() in V1_COLOURS:
        colour = mc.group(1).lower()
    pat, letters, codes = None, [], []
    mp = re.search(r'^(F[A-Z]-[A-Z0-9]+)\s+\[([A-Z ·]+)\]', cap, re.M)
    if mp:
        pat, letters = mp.group(1), [x.strip() for x in mp.group(2).split('·')]
        mcodes = [re.sub(r'\(?3m\)?$', '', c) for c in meta.get('codes', [])]
        codes = [c for c in mcodes if c.startswith(pat) and c[-1] in letters]
        if len(codes) != len(letters):
            codes = []
    else:
        for c in CODE_RE.findall(URL_RE.sub(' ', body)):
            c = re.sub(r'\(?3m\)?$', '', c)
            if c not in codes:
                codes.append(c)
    lines = []
    for ln in body.splitlines():
        mm = re.match(r'^\s*((?:F[A-Z]-[A-Z0-9-]+(?:\(3m\))?\s*/?\s*)+)\s+¥([\d,]+)\s*$', ln)
        if mm:
            lines.append((' / '.join(x.strip() for x in mm.group(1).split('/') if x.strip()), int(mm.group(2).replace(',', ''))))
    prices = sorted({int(p.replace(',', '')) for p in re.findall(r'¥([\d,]+)', body)})
    kind = meta.get('collection') or ('template' if meta.get('source') == 'template' else None)
    return {
        'kind': kind, 'codes': codes, 'sold_out': sold, 'prices': prices,
        'price': ('¥{:,}'.format(prices[0]) if len(prices) == 1 else None),
        'price_lines': lines if len(prices) > 1 else [],
        'price_from': bool(re.search(r'from ¥|¥[\d,]+〜', cap)),
        'colour': colour, 'pattern': pat, 'letters': letters, 'n': len(codes),
        'offer_11_for_10': 'buy 11, get 1 free' in cap.lower() or 'Buy 11, get 1 free' in cap,
        'tax_incl': 'tax incl.' in cap or '税込' in cap,
        'cod': any(x in cap for x in ('အိမ်ရောက်ငွေချေ', 'cash on delivery', '代金引換')),
        'payments': 'PayPay' in cap and ('Credit Card' in cap or 'credit card' in cap) and ('Bank' in cap or 'bank' in cap),
        'delivery_japan': any(x in cap for x in ('ဂျပန်ပြည်တွင်း', 'Delivery across Japan', '全国発送')),
        'messenger_link': MESSENGER if MESSENGER in cap else None,
    }


class _Track(dict):
    def __init__(self, *a, **k):
        super().__init__(*a, **k)
        self.used = set()

    def __getitem__(self, k):
        self.used.add(k)
        return super().__getitem__(k)


def build_rewrite(lang, facts, pools, qid):
    """intro (kind) + price part + CTA [+ sold-out note], filled ONLY with facts parsed from this row.
    Optional phrases (tax incl., the 11-for-10 offer, 'from') appear only when the row states them."""
    P = pools.get(lang) or {}
    kind = facts['kind'] if facts['kind'] in ('pattern', 'colour', 'new', 'template') else 'new'
    if kind == 'pattern' and not (facts['pattern'] and facts['letters']):
        kind = 'new'
    if kind == 'colour' and not facts['colour']:
        kind = 'new'
    if not facts['messenger_link'] or not facts['codes'] or not (facts['price'] or facts['price_lines']):
        return None, None
    key = 'intro_nocodes' if facts['price_lines'] and kind != 'pattern' else 'intro'
    intro_pool = (P.get(key) or {}).get(kind) or []
    if not intro_pool:
        return None, None
    col = V1_COLOURS.get(facts['colour'] or '', ('', '', ''))
    lets = facts['letters']
    if lang == 'ja':
        letters = '・'.join(lets)
    else:
        joiner = {'en': ' and ', 'ne': ' ra ', 'my': ' / '}[lang]
        letters = (', '.join(lets[:-1]) + joiner + lets[-1]) if len(lets) > 1 else (lets[0] if lets else '')
    yen_ja = lambda v: '{:,}円'.format(v)
    d = _Track({
        'codes': ' / '.join(facts['codes']), 'pattern': facts['pattern'] or '', 'letters': letters,
        'n': facts['n'], 'price': facts['price'] or '', 'price_ja': (facts['price'] or '').replace('¥', '') + '円',
        'price_lines': '\n'.join(f'{c}  ¥{v:,}' for c, v in facts['price_lines']),
        'price_lines_ja': '\n'.join(f'{c}  {yen_ja(v)}' for c, v in facts['price_lines']),
        'colour_en': col[0], 'colour_en_lower': col[0].lower(), 'colour_ja': col[1], 'colour_ne': col[2],
        'link': facts['messenger_link'],
    })
    pick = lambda xs: xs[qid % len(xs)]
    intro = pick(intro_pool).format_map(d)
    if lang in ('en', 'ne') and intro[:1].islower():
        intro = intro[0].upper() + intro[1:]
    pr = P['price']
    if facts['price_lines']:
        price = pick(pr['multi']).format_map(d)
        if facts['tax_incl']:
            price += pr['tax_multi']
    else:
        base = pr['from'] if facts['price_from'] else pick(pr['single'])
        price = base.format_map(d) + (pr['tax'] if facts['tax_incl'] else '')
        price += pr['offer'] if facts['offer_11_for_10'] else pr.get('end', '')
    cta = pick(P['cta']).format_map(d)
    parts = [intro, price, cta]
    if facts['sold_out'] and P.get('sold_out'):
        parts.append(P['sold_out'].format(so=' / '.join(facts['sold_out'])))
    text = '\n'.join(x for x in parts if x)
    used = {'codes': facts['codes'], 'messenger_link': facts['messenger_link']}
    if facts['price_lines']:
        used['price_lines'] = facts['price_lines']
    else:
        used['price'] = facts['price']
    for k, fk in (('pattern', 'pattern'), ('letters', 'letters'), ('n', 'n'), ('colour_en', 'colour'),
                  ('colour_en_lower', 'colour'), ('colour_ja', 'colour'), ('colour_ne', 'colour')):
        if k in d.used:
            used[fk] = facts[fk]
    if facts['tax_incl']:
        used['tax_incl'] = True
    if facts['offer_11_for_10'] and not facts['price_lines']:
        used['offer_11_for_10'] = True
    if facts['price_from'] and not facts['price_lines']:
        used['price_from'] = True
    if facts['sold_out']:
        used['sold_out'] = facts['sold_out']
    if any(v in (None, '', []) for v in used.values()):
        return None, None
    return text, used


# ------------------------------------------------------------ analyse
ANALYST_NOTES = {   # Claude-coded annotations of competitor posts (fi_competitor_posts.id), documented in method doc
    3: {'human_markers': ['hashtag typo (#swostikgaesha)']},
    4: {'notes': 'reply-to-comment video: answers a viewer question on screen'},
    5: {'human_markers': ['hashtag typo (#swostikgaesha)']},
    6: {'human_markers': ['non-native grammar ("our all the ...")'], 'notes': 'gratitude post, warm and imperfect'},
    10: {'human_markers': ['typo "Orginal"'], 'notes': 'English + romanized Nepali + romaji politeness in one run'},
    11: {'notes': 'location + price + festival + inbox CTA in one run-on sentence'},
}
OWN_ANCHORS = ['japan ပြန်လက်ဆောင်', 'ဂျပန်ပြည်တွင်း&မြန်မာ', 'အဆင်သစ်တွေအများကြီး', 'Instock ဖြည့်ထားပါတယ်']


def build_samples(bundle, inbox):
    samples = []
    posts = bundle['competitor_posts']
    nepali_aud = [p for p in posts if p.get('target_audience') == 'nepali' and p.get('caption')]
    dev_body = sum(1 for p in nepali_aud if DEV_RE.search(split_body(p['caption'])[0]))
    dev_evidence = (f'measured: {dev_body}/{len(nepali_aud)} Nepali-audience seller captions use Devanagari in body '
                    f'text') if len(nepali_aud) >= 5 and dev_body == 0 else None
    for p in posts:
        base = {'source_ref': f"fi_competitor_posts:{p['id']}", 'competitor_post_id': p['id'],
                'platform': p['platform'], 'handle': p.get('handle'), 'internal_only': True,
                'method_version': METHOD_VERSION, 'target_audience': p.get('target_audience')}
        if not p.get('caption'):
            ai = 0.6 if p.get('ai_generated_suspected') else None
            reason = 'no_caption_text (image-only owner crop)' + ('; ai_image_suspected' if ai else '')
            samples.append(dict(base, fingerprint=fp('owner_screenshot', p['id'], ''), source='owner_screenshot',
                                language='none', script=None, script_detail=None, read_confidence=None, text=None,
                                overlay_text=None, ai_likeness=ai,
                                ai_reasons={'signals': [{'code': 'S11_ai_image_suspected', 'weight': 0.6}]} if ai else None,
                                included=False, exclusion_reason=reason, features={'content_kind': p.get('content_kind')}))
            continue
        a = analyse_text(p['caption'])
        note = ANALYST_NOTES.get(p['id'], {})
        score, sig, hum, notes = ai_rubric(a, {'ai_image_suspected': p.get('ai_generated_suspected'),
                                               'human_markers': note.get('human_markers'),
                                               'devanagari_body_evidence': dev_evidence})
        ov = clean_overlay(p.get('on_screen_text'))
        feats = dict(a['features'], analyst_note=note.get('notes'), content_kind=p.get('content_kind'),
                     overlay=analyse_text(ov)['features'] if ov else None)
        ai_flag = score >= AI_THRESHOLD
        samples.append(dict({**base, **a}, fingerprint=fp('tiktok_oembed', p['id'], p['caption']), source='tiktok_oembed',
                            read_confidence=1.0, text=p['caption'].strip(), overlay_text=ov, features=feats,
                            ai_likeness=score, ai_reasons={'signals': sig, 'human_markers': hum, 'notes': notes},
                            included=not ai_flag,
                            exclusion_reason=('ai_looking: ' + '; '.join(s['code'] for s in sig)) if ai_flag else None))
    # own page: paragraphs of published captions that the builder attributes to the page's past posts
    paras = collections.OrderedDict()
    for row in bundle['all_published']:
        for para in re.split(r'\n\s*\n', row['caption'] or ''):
            if any(anc in para for anc in OWN_ANCHORS):
                paras.setdefault(para.strip(), []).append(row['id'])
    for para, ids in paras.items():
        a = analyse_text(para, 'my')
        score, sig, hum, notes = ai_rubric(a)
        samples.append(dict(a, fingerprint=fp('own_page_phrases', '', para), source='own_page_phrases',
                            source_ref=f'autoposter_queue published captions ({len(ids)} posts, first id {ids[0]}); '
                                       'CollectionPostBuilder::caption() says these are phrases from the page\'s own past posts',
                            competitor_post_id=None, platform='facebook', handle='fujiba_page (own)',
                            read_confidence=0.7, text=para, overlay_text=None,
                            features=dict(a['features'], own_page=True, seen_in_posts=len(ids)),
                            ai_likeness=score, ai_reasons={'signals': sig, 'human_markers': hum, 'notes': notes},
                            included=score < AI_THRESHOLD, internal_only=True, method_version=METHOD_VERSION,
                            exclusion_reason=None if score < AI_THRESHOLD else 'ai_looking'))
    for it in (inbox or {}).get('items', []):
        a = analyse_text(it['text'], it['lang'])
        score, sig, hum, notes = ai_rubric(a, {'devanagari_body_evidence': dev_evidence})
        ok = score < AI_THRESHOLD and (it['source'] != 'tesseract_ocr' or it.get('verified'))
        reason = None if ok else ('ocr_unverified' if score < AI_THRESHOLD else 'ai_looking: ' + '; '.join(s['code'] for s in sig))
        samples.append(dict(a, fingerprint=fp(it['source'], it['key'], it['text']), source=it['source'],
                            source_ref='inbox:' + ','.join(it['files']), competitor_post_id=None,
                            platform=it.get('platform'), handle=it.get('handle'), read_confidence=it.get('confidence'),
                            text=it['text'], overlay_text=None, features=a['features'], ai_likeness=score,
                            ai_reasons={'signals': sig, 'human_markers': hum, 'notes': notes}, included=ok,
                            exclusion_reason=reason, internal_only=True, method_version=METHOD_VERSION))
    return samples, {'nepali_audience_captions': len(nepali_aud), 'devanagari_body': dev_body, 'evidence': dev_evidence}


def _ngrams(samples, lang, top=12):
    df = collections.Counter()
    for s in samples:
        body = split_body(s['text'])[0]
        if lang == 'my':
            toks = my_syllables(body)
        else:
            toks = tokens_latin(body)
        seen = set()
        for n in (2, 3) if lang == 'my' else (1, 2, 3):
            for i in range(len(toks) - n + 1):
                g = tuple(toks[i:i + n])
                if lang != 'my' and n == 1 and g[0] in {'the', 'to', 'for', 'all', 'and', 'in', 'us', 'of', 'a', 'we',
                                                        'you', 'is', 'our', 'more', 'please', 'are'}:
                    continue
                seen.add(g)
        df.update(seen)
    joiner = '' if lang == 'my' else ' '
    keep = [(g, c) for g, c in df.most_common(80) if c >= 2]

    def inside(small, big):
        return len(small) < len(big) and any(big[i:i + len(small)] == small for i in range(len(big) - len(small) + 1))
    keep = [(g, c) for g, c in keep if not any(c2 == c and inside(g, g2) for g2, c2 in keep)]
    return [[joiner.join(g), c] for g, c in keep][:top]


def _share(samples, pred):
    return sum(1 for s in samples if pred(s))


def aggregate(lang, inc, excluded, extra=None):
    n = len(inc)
    rows = []
    n_body_all = sum(1 for s in inc if not s['features'].get('hashtags_only'))
    status = 'established' if n_body_all >= MIN_ESTABLISHED else 'provisional'

    def row(metric, value, notes=None, size=None):
        rows.append({'language': lang, 'metric': metric, 'value': value, 'sample_size': n if size is None else size,
                     'method': 'measured', 'status': status, 'notes': notes, 'method_version': METHOD_VERSION})

    handles = collections.Counter(s.get('handle') for s in inc)
    row('sample_inventory', {'included': n, 'with_body_text': n_body_all, 'excluded': len(excluded), 'sellers': len(handles),
                             'by_source': dict(collections.Counter(s['source'] for s in inc)),
                             'by_handle': dict(handles),
                             'excluded_reasons': dict(collections.Counter((s.get('exclusion_reason') or '').split(':')[0]
                                                                          for s in excluded))},
        'provisional: fewer than 10 included human samples with body text' if status == 'provisional' else None)
    if not n:
        return rows, status
    body = [s for s in inc if not s['features'].get('hashtags_only')]
    row('length', {'words': {'median': med([s['word_count'] for s in body]), 'range': rng([s['word_count'] for s in body])},
                   'word_units': {'median': med([s['unit_count'] for s in body]), 'range': rng([s['unit_count'] for s in body])},
                   'lines': {'median': med([s['line_count'] for s in body]), 'range': rng([s['line_count'] for s in body])},
                   'chars': {'median': med([s['char_count'] for s in body])}, 'n_with_body': len(body)},
        'words = whitespace tokens excl. hashtags/mentions/urls; word_units = Burmese syllables / ja chars÷2 / words')
    em = collections.Counter()
    for s in inc:
        em.update(s['emoji_list'] or {})
    row('emoji', {'posts_with_emoji': _share(inc, lambda s: s['emoji_count'] > 0),
                  'per_post': {'median': med([s['emoji_count'] for s in inc]), 'range': rng([s['emoji_count'] for s in inc])},
                  'top': em.most_common(10)})
    ht = collections.Counter()
    tag_scripts = collections.Counter()
    stems = {re.sub(r'[^a-z]', '', (s.get('handle') or '').lower()) for s in inc}
    stems = {st for st in stems if len(st) >= 6}

    def shopish(tag):
        t = re.sub(r'[^a-z]', '', tag.lower())
        return any(t[i:i + 6] in st for st in stems for i in range(max(0, len(t) - 5)))
    for s in inc:
        ht.update({h.lower() for h in s['hashtags'] or []})
        for h in s['hashtags'] or []:
            c = script_counts(h)
            tag_scripts[max(('latin', 'ne_dev', 'my', 'ja'), key=lambda k: c[k])] += 1
    row('hashtags', {'posts_with_hashtags': _share(inc, lambda s: s['hashtag_count'] > 0),
                     'per_post': {'median': med([s['hashtag_count'] for s in inc]), 'range': rng([s['hashtag_count'] for s in inc])},
                     'scripts': dict(tag_scripts),
                     'shop_name_tags_posts': _share(inc, lambda s: any(shopish(h) for h in s['hashtags'] or [])),
                     'top_generic': [[h, c] for h, c in ht.most_common(20) if c >= 2 and not shopish(h)][:8]},
        'top_generic = tags in >=2 posts that are not the seller\'s own shop name')
    zaw = _share(inc, lambda s: s['script'] == 'my_zawgyi')
    row('script', {'scripts': dict(collections.Counter(s['script'] for s in inc)), 'zawgyi': zaw,
                   'english_mix_ratio': {'median': med([s['english_mix_ratio'] for s in body]),
                                         'range': rng([s['english_mix_ratio'] for s in body])},
                   'devanagari_in_body': _share(body, lambda s: s['script_detail']['body_chars']['ne_dev'] > 0),
                   'devanagari_in_hashtags_only': _share(inc, lambda s: s['script_detail']['hashtag_chars']['ne_dev'] > 0
                                                         and s['script_detail']['body_chars']['ne_dev'] == 0),
                   'romanized_nepali': _share(inc, lambda s: s['script'] == 'ne_roman'),
                   'romaji_tokens': sorted({t for s in inc for t in s['features']['romaji_tokens']})})
    row('tone', dict(collections.Counter(s['tone'] for s in body)))
    addr = collections.Counter()
    for s in body:
        addr.update(s['features']['address_terms'])
    row('address', {'terms': dict(addr), 'posts_with_address': _share(body, lambda s: bool(s['features']['address_terms']))})
    hooks = collections.Counter()
    for s in inc:
        hooks.update(s['features']['first_line_hook'])
    row('opening_hook', dict(hooks.most_common()))
    for metric, key in (('selling_points', 'selling_points'), ('price', 'price'), ('cta', 'cta'),
                        ('delivery', 'delivery'), ('stock', 'stock')):
        c = collections.Counter()
        kw = collections.defaultdict(collections.Counter)
        for s in inc:
            for cat, words in s['features'][key].items():
                c[cat] += 1
                kw[cat].update(words)
        row(metric, {'posts_with_any': _share(inc, lambda s: bool(s['features'][key])),
                     'categories': dict(c.most_common()),
                     'keywords': {cat: [w for w, _ in kw[cat].most_common(3)] for cat in c}})
    ov = [s for s in inc if s.get('overlay_text')]
    if ov:
        oc = collections.Counter()
        for s in ov:
            o = s['features'].get('overlay') or {}
            for grp in ('price', 'cta', 'stock', 'delivery'):
                for cat in (o.get(grp) or {}):
                    oc[f'{grp}:{cat}'] += 1
        row('overlay_text', {'videos_with_overlay': len(ov), 'categories': dict(oc.most_common())},
            'on-screen cover text of TikTok videos (read from thumbnails by WS-I)', size=len(ov))
    row('phrases', {'top_ngrams_doc_freq': _ngrams(body, lang)}, 'document frequency (posts containing the n-gram), n<=3')
    if extra:
        for metric, value, notes, size in extra:
            row(metric, value, notes, size)
    return rows, status


def cmd_analyse(args):
    bundle = json.load(open(args.bundle, encoding='utf-8'))
    content = json.load(open(args.content, encoding='utf-8'))
    inbox = json.load(open(args.inbox, encoding='utf-8')) if args.inbox and os.path.exists(args.inbox) else None
    samples, aud = build_samples(bundle, inbox)

    style, status = [], {}
    for lang in LANGS:
        inc = [s for s in samples if s['language'] == lang and s['included'] and s.get('text')]
        exc = [s for s in samples if s['language'] == lang and not s['included']]
        extra = []
        if lang == 'ne':
            na = [s for s in samples if s.get('target_audience') == 'nepali' and s.get('text')]
            extra.append(('audience_script_choice', {
                'nepali_audience_captions': len(na),
                'body_language': dict(collections.Counter(s['language'] for s in na)),
                'body_script': dict(collections.Counter(s['script'] for s in na)),
                'devanagari_in_body': sum(1 for s in na if s['script_detail']['body_chars']['ne_dev'] > 0),
                'devanagari_hashtag_only': sum(1 for s in na if s['script_detail']['hashtag_chars']['ne_dev'] > 0
                                               and s['script_detail']['body_chars']['ne_dev'] == 0),
                'accounts': len({s['handle'] for s in na})},
                'all captions from accounts whose audience is Nepali (fi_competitors.target_audience), any body language',
                len(na)))
        rows, st = aggregate(lang, inc, exc, extra)
        status[lang] = st
        style += rows
        mk = content['languages'][lang].get('model_knowledge', {})
        for metric, bullets in mk.items():
            style.append({'language': lang, 'metric': metric, 'value': bullets, 'sample_size': 0,
                          'method': 'model_knowledge', 'status': st,
                          'notes': 'model_knowledge (not measured): typical small-seller practice, not counted from data',
                          'method_version': METHOD_VERSION})

    # ---- copy guards
    third = [(f"sample:{s['source']}:{s.get('competitor_post_id') or s.get('source_ref', '')[:30]}", s['text'])
             for s in samples if s.get('text')]
    third += [(f"overlay:{s.get('competitor_post_id')}", s['overlay_text']) for s in samples if s.get('overlay_text')]
    guard = CopyGuard(third)
    fresh = CopyGuard(third + [(f"v1:{r['id']}", r['caption']) for r in bundle['all_published']])

    # ---- our last 50 v1 captions
    ours = bundle['ours_last50']
    seg_rows = {r['id']: segment_v1(r['caption']) for r in ours}
    common = {}
    for lang in ('my', 'ne', 'ja', 'en', 'shared'):
        freq = collections.Counter()
        having = [s[lang] for s in seg_rows.values() if lang in s]
        for text in having:
            freq.update({_mask(x) for x in text.splitlines() if x.strip()})
        common[lang] = {ln for ln, c in freq.items() if having and c >= 0.8 * len(having)}
    scores = []
    v1_stats = collections.defaultdict(list)
    for r in ours:
        facts = v1_facts(r)
        segs = seg_rows[r['id']]
        parallel = '🇯🇵' in r['caption'] and '🇬🇧' in r['caption']
        head = first_line(r['caption'])
        head_parts = [x.strip() for x in head.split(' · ')] if _symmetric_emoji(head) else []
        for lang in ('my', 'ne', 'ja', 'en', 'shared'):
            if lang not in segs:
                continue
            text = segs[lang]
            a = analyse_text(text, lang if lang != 'shared' else None)
            lines = [x for x in text.splitlines() if x.strip()]
            rep = sum(1 for x in lines if _mask(x) in common[lang]) / len(lines) if lines else 0
            ctx = {'parallel_translation': parallel and lang in ('ja', 'en'), 'repetition_ratio': rep,
                   'devanagari_body_evidence': aud['evidence'],
                   'symmetric_header': bool(head_parts) and any(hp and hp in lines for hp in head_parts)}
            score, sig, hum, notes = ai_rubric(a, ctx)
            if lang == 'shared':
                notes = notes + [f"{a['hashtag_count']} hashtags in {len({k for k, v in a['script_detail']['hashtag_chars'].items() if v and k != 'fancy'})} scripts"]
                ai = False
            else:
                ai = score >= AI_THRESHOLD
            if lang == 'my':
                if text.count('Messenger မှာ မှာယူနိုင်ပါတယ်ရှင့်') >= 2:
                    notes.append('same Messenger CTA sentence appears twice in this post')
                if '(再入荷待ち)' in text:
                    notes.append('Japanese gloss (再入荷待ち) inside a Burmese line')
            v1_stats[lang].append((a['word_count'], a['emoji_count'], ai, a['unit_count'], a['char_count']))
            rw, used, ov = None, None, None
            if ai:
                rw, used = build_rewrite(lang, facts, content['rewrites'], r['id'])
                if rw:
                    ov = guard.check(rw)
                    if not ov['passed']:
                        rw = None
            scores.append({'queue_id': r['id'], 'segment_language': lang, 'segment_text': text, 'ai_sounding': ai,
                           'ai_score': score, 'reasons': {'signals': sig, 'human_markers': hum, 'notes': notes,
                                                          'band': 'ai' if score >= AI_THRESHOLD else 'borderline' if score >= BORDERLINE else 'human'},
                           'rewrite_suggestion': rw, 'facts_used': used, 'overlap_check': ov,
                           'method_version': METHOD_VERSION, 'published_at': r['published_at'], 'kind': facts['kind']})

    # ---- content checks (examples + drafts)
    prods = {p['name']: p for p in bundle['draft_products']}
    groups = {'FV-67': {'codes': ['FV-67-A', 'FV-67-B', 'FV-67-C'], 'sold_out': False},
              'FV-73-A': {'codes': ['FV-73-A'], 'sold_out': True}, 'FV-83-A': {'codes': ['FV-83-A'], 'sold_out': True}}
    for g, info in groups.items():
        for c in info['codes']:
            p = prods.get(c)
            if not p:
                raise SystemExit(f'draft product {c} missing from bundle')
            if int(p['price']) != 1595:
                raise SystemExit(f'{c} price changed to {p["price"]}: update drafts before rendering')
            in_stock = p['status'] == 1 and p['qty'] > 0
            if info['sold_out'] == in_stock:
                raise SystemExit(f'{c} stock status changed (status={p["status"]} qty={p["qty"]}): review drafts')
    checks = {'examples': [], 'drafts': []}
    for lang in LANGS:
        L = content['languages'][lang]
        for kind in ('openings', 'ctas'):
            for i, t in enumerate(L[kind], 1):
                g = fresh.check(t)
                extra = []
                if lang == 'my' and zawgyi_detect(t)['label'] not in ('unicode', 'none', 'uncertain'):
                    extra.append('burmese_not_unicode')
                if '細布' in t or SUPPLIER_CODE_RE.search(t) or LEN_RE.search(URL_RE.sub(' ', t)):
                    extra.append('forbidden_fact')
                checks['examples'].append({'lang': lang, 'kind': kind, 'n': i, 'guard': g, 'problems': extra})
        for g, drafts in L['drafts'].items():
            info = groups[g]
            for i, d in enumerate(drafts, 1):
                probs = fact_check(d, info, lang, info['sold_out'])
                gc = fresh.check(d)
                a = analyse_text(d.replace('[' + DNP + ']', ''), lang)
                sc, sig, hum, _ = ai_rubric(a)
                checks['drafts'].append({'lang': lang, 'group': g, 'n': i, 'guard': gc, 'problems': probs,
                                         'ai_score': sc, 'ai_signals': [s['code'] for s in sig],
                                         'script': a['script'], 'zawgyi': (a['script_detail'].get('zawgyi') or {}).get('label')})

    out = {
        'generated_at': datetime.now(timezone.utc).isoformat(), 'method_version': METHOD_VERSION,
        'thresholds': {'ai': AI_THRESHOLD, 'borderline': BORDERLINE, 'established_min': MIN_ESTABLISHED},
        'weights': WEIGHTS, 'bundle_generated_at': bundle['generated_at'], 'status': status,
        'nepali_audience': aud, 'samples': samples, 'style': style, 'scores': scores, 'content_checks': checks,
        'guard_corpus': {'third_party_and_own_page_texts': guard.size, 'with_v1_published': fresh.size},
        'ours_range': {'ids': [ours[-1]['id'], ours[0]['id']], 'published': [ours[-1]['published_at'], ours[0]['published_at']]},
        'draft_products': bundle['draft_products'],
        'v1_contrast': {lang: {'segments': len(v), 'ai_sounding': sum(1 for x in v if x[2]),
                               'words': {'median': med([x[0] for x in v]), 'range': rng([x[0] for x in v])},
                               'emoji': {'median': med([x[1] for x in v]), 'range': rng([x[1] for x in v])},
                               'units': {'median': med([x[3] for x in v]), 'range': rng([x[3] for x in v])},
                               'chars': {'median': med([x[4] for x in v]), 'range': rng([x[4] for x in v])}}
                        for lang, v in v1_stats.items()},
    }
    write_text(args.out, json.dumps(out, ensure_ascii=False, indent=1, default=str))
    bad = [c for c in checks['examples'] + checks['drafts'] if not c['guard']['passed'] or c['problems']]
    print(f"analyse: {len(samples)} samples ({sum(s['included'] for s in samples)} included), {len(style)} style rows, "
          f"{len(scores)} score rows, content problems={len(bad)} -> {args.out}")
    for c in bad[:30]:
        print('  PROBLEM', c['lang'], c.get('group', c.get('kind')), c['n'], c['problems'], c['guard']['hits'][:2])
    return 1 if bad else 0


# ------------------------------------------------------------ render
def _num(x):
    if isinstance(x, float):
        return str(int(x)) if x.is_integer() else f'{x:.2f}'.rstrip('0').rstrip('.')
    return str(x)


def _fmt_rng(v):
    if not v or v.get('median') is None:
        return 'n/a'
    r = v.get('range')
    if r and r[0] == r[1]:
        return f'{_num(r[0])} (all)'
    return f"median {_num(v.get('median'))}" + (f" (range {_num(r[0])}–{_num(r[1])})" if r else '')


def _style(analysis, lang, metric, method='measured'):
    for r in analysis['style']:
        if r['language'] == lang and r['metric'] == metric and r['method'] == method:
            return r
    return None


def _cs(d, top=6):
    return ', '.join(f'{k} {v}' for k, v in list((d or {}).items())[:top]) or 'none'


def measured_bullets(analysis, lang):
    g = lambda m: (_style(analysis, lang, m) or {}).get('value')
    inv = g('sample_inventory')
    n = inv['included']
    if not n:
        return []
    L, E, H, S, T = g('length'), g('emoji'), g('hashtags'), g('script'), g('tone')
    A, O, SP, PR, C = g('address'), g('opening_hook'), g('selling_points'), g('price'), g('cta')
    DV, ST, PH, OV, AUD = g('delivery'), g('stock'), g('phrases'), g('overlay_text'), g('audience_script_choice')
    nb = L['n_with_body']
    out = [f"Length: {_fmt_rng(L['words'])} words, {_fmt_rng(L['lines'])} line(s) per caption (n={nb} with body text)"
           + (f"; Burmese syllables {_fmt_rng(L['word_units'])}" if lang == 'my' else '') + '.',
           f"Emoji: in {E['posts_with_emoji']}/{n} captions, {_fmt_rng(E['per_post'])} per caption; seen: "
           + (' '.join(f'{e}×{c}' for e, c in E['top']) or 'none') + '.',
           f"Hashtags: in {H['posts_with_hashtags']}/{n}, {_fmt_rng(H['per_post'])} per caption; by script {_cs(H['scripts'])}; "
           f"own shop-name tag in {H['shop_name_tags_posts']}/{n}; generic tags in ≥2 captions: "
           + (', '.join(f'{h} ({c})' for h, c in H['top_generic']) or 'none') + '.']
    sc = f"Script: {_cs(S['scripts'])}; Zawgyi {S['zawgyi']}; English-mix ratio {_fmt_rng(S['english_mix_ratio'])}"
    if lang == 'ne' and AUD:
        sc += (f"; across all {AUD['nepali_audience_captions']} Nepali-audience captions ({AUD['accounts']} accounts): body "
               f"language {_cs(AUD['body_language'])}, Devanagari in body {AUD['devanagari_in_body']}, Devanagari only in "
               f"hashtags {AUD['devanagari_hashtag_only']}")
    if S['romaji_tokens']:
        sc += f"; Japanese words in romaji: {', '.join(S['romaji_tokens'])}"
    out.append(sc + '.')
    out.append(f"Tone: {_cs(T)}. Direct address in {A['posts_with_address']}/{nb}: {_cs(A['terms'], 8)}.")
    out.append(f"First line: {_cs(O, 7)}.")
    out.append(f"Selling points (captions): {_cs(SP['categories'], 8)}.")
    pr = f"Price in caption: {PR['posts_with_any']}/{n}" + (f" ({_cs(PR['categories'])})" if PR['categories'] else '')
    if OV:
        ovp = {k.split(':', 1)[1]: v for k, v in OV['categories'].items() if k.startswith('price:')}
        pr += f"; on video cover text: {sum(1 for _ in ovp) and max(ovp.values())}/{OV['videos_with_overlay']} ({_cs(ovp)})"
    out.append(pr + '.')
    out.append(f"CTA: {C['posts_with_any']}/{n} ({_cs(C['categories'])}). Delivery wording {DV['posts_with_any']}/{n} "
               f"({_cs(DV['categories'])}); stock wording {ST['posts_with_any']}/{n} ({_cs(ST['categories'])}).")
    if PH and PH['top_ngrams_doc_freq']:
        out.append('Most shared phrases (captions containing, ≤3 words): '
                   + ', '.join(f'"{p}" {c}' for p, c in PH['top_ngrams_doc_freq'][:8]) + '.')
    return out


def render_style(analysis, content, lang):
    L = content['languages'][lang]
    inv = _style(analysis, lang, 'sample_inventory')['value']
    n = inv['included']
    nb = inv.get('with_body_text', n)
    aud = (_style(analysis, 'ne', 'audience_script_choice') or {}).get('value') or {}
    subst = lambda t: t.replace('{n}', str(nb)).replace('{n_aud}', str(aud.get('nepali_audience_captions', 0)))
    st = analysis['status'][lang]
    v1 = analysis.get('v1_contrast', {}).get(lang)
    lines = [f"# Caption style — {L['name']} ({lang})", '',
             f"**Status: {st.upper()}** · {n} included human sample(s) ({nb} with body text) from {inv['sellers']} "
             f"seller(s)" + (' · fewer than 10 → provisional' if st == 'provisional' else '')
             + f" · excluded {inv['excluded']} · rubric {METHOD_VERSION} · data {analysis['bundle_generated_at'][:10]}",
             '', f"Sources: {', '.join(f'{k} {v}' for k, v in inv['by_source'].items()) or 'none yet'}. "
                 f"{L.get('source_note', '')}", '', f"## Measured (n={nb})"]
    mb = measured_bullets(analysis, lang)
    if not mb:
        lines.append('- Nothing measured yet: no human caption in this language has been collected. Every point below '
                     f'is model knowledge until screenshots arrive in `/root/incoming/caption-screens/{lang}/`.')
    lines += [f'- {b}' for b in mb]
    if v1:
        size = {'ja': f"{_fmt_rng(v1['chars'])} characters", 'my': f"{_fmt_rng(v1['units'])} syllables"}.get(
            lang, f"{_fmt_rng(v1['words'])} words")
        lines.append(f"- Our v1 {lang} block (last 50 posts): {size}, {_fmt_rng(v1['emoji'])} emoji; "
                     f"AI-sounding in {v1['ai_sounding']}/{v1['segments']} (see caption-scores-v1.md).")
    lines += ['', '## Model knowledge (not measured)']
    for metric, bullets in L.get('model_knowledge', {}).items():
        for b in bullets[:2]:
            lines.append(f'- {b}')
    lines += ['', '## Do']
    lines += [f"- {d['text']} _[{subst(d['basis'])}]_" for d in L['do']]
    lines += ['', "## Don't"]
    lines += [f"- {d['text']} _[{subst(d['basis'])}]_" for d in L['dont']]
    ex = {(c['kind'], c['n']): c for c in analysis['content_checks']['examples'] if c['lang'] == lang}
    ok_o = all(ex[('openings', i)]['guard']['passed'] and not ex[('openings', i)]['problems'] for i in range(1, len(L['openings']) + 1))
    ok_c = all(ex[('ctas', i)]['guard']['passed'] and not ex[('ctas', i)]['problems'] for i in range(1, len(L['ctas']) + 1))
    lines += ['', f"## 10 openings (written fresh · copy guard {'passed' if ok_o else 'FAILED'})"]
    lines += [f'{i}. {t}' for i, t in enumerate(L['openings'], 1)]
    lines += ['', f"## 10 CTAs (written fresh · copy guard {'passed' if ok_c else 'FAILED'})"]
    lines += [f'{i}. {t}' for i, t in enumerate(L['ctas'], 1)]
    lines += ['', f"_Copy guard: none of the lines above shares a run of ≥6 words / ≥20 Burmese / ≥12 Japanese characters "
                  f"with the {analysis['guard_corpus']['with_v1_published']} collected + published v1 texts. Quoted phrases "
                  f"above are ≤3-word n-grams. Collected captions stay internal (fi_caption_samples.internal_only)._"]
    return '\n'.join(lines) + '\n'


def render_method(analysis, content, selftest):
    samples = analysis['samples']
    by_src = collections.defaultdict(lambda: [0, 0])
    for s in samples:
        by_src[s['source']][0] += 1
        by_src[s['source']][1] += 1 if s['included'] else 0
    excl = collections.Counter((s.get('exclusion_reason') or '').split(' (')[0].split(':')[0] for s in samples if not s['included'])
    human_max = max((s['ai_likeness'] or 0) for s in samples if s.get('text'))
    seg = collections.defaultdict(list)
    for r in analysis['scores']:
        seg[r['segment_language']].append(r['ai_score'])
    ex = analysis['content_checks']['examples'] + analysis['content_checks']['drafts']
    lines = ['# Caption-style research: method, sources, limits (WS-K, rubric ' + METHOD_VERSION + ')', '',
             'Goal: learn how real human sellers write post text in Burmese, Nepali, Japanese and English so captions '
             'sound like a shop owner. Staging only: v1 captions, templates and CaptionGenerator are untouched; nothing '
             'is posted or queued.', '',
             '## Sources (only legitimate ones)', '',
             '| source | rows | included | what it is |', '|---|---|---|---|']
    desc = {'tiktok_oembed': 'captions already in fi_competitor_posts, fetched earlier via TikTok official oEmbed',
            'owner_screenshot': 'owner Instagram screenshots in fi_competitor_posts: image-only crops, no caption text',
            'own_page_phrases': "paragraphs of our published captions that CollectionPostBuilder attributes to the page's own past posts",
            'claude_read': 'owner screenshots (my/ne) transcribed by Claude from the image',
            'tesseract_ocr': 'owner screenshots (en/ja) OCR-ed, verified against the image',
            'owner_paste': 'caption text copied by the owner into the inbox'}
    for src, (rows, inc) in sorted(by_src.items()):
        lines.append(f'| {src} | {rows} | {inc} | {desc.get(src, "")} |')
    ai_img = sum(1 for x in samples if 'ai_image_suspected' in (x.get('exclusion_reason') or ''))
    lines += ['', f"Excluded: {_cs(excl)} ({ai_img} of them also flagged as AI-generated images; 0 captions excluded as "
                  f"AI-written). No new network calls were made: every TikTok URL in the DB already had its "
                  "oEmbed caption and the owner gave no new URLs (any future call: official oEmbed only, ≤1 request/5 s, "
                  "UA FujibaResearch/1.0). No Facebook/Instagram scraping, no profile enumeration. Inbox "
                  "`/root/incoming/caption-screens/{my,ne,ja,en}/` (mode 700, README.txt) had 0 files at analysis time.", '',
              f"Our captions: last 50 published autoposter_queue rows (ids {analysis['ours_range']['ids'][0]}–"
              f"{analysis['ours_range']['ids'][1]}), split into my / ne / ja / en / shared segments.", '',
              '## Script detection', '',
              '- Language of a caption = script with most letters in the body (hashtags, @mentions, URLs removed); Latin '
              'text is Nepali when it has ≥2 strong romanized-Nepali tokens (lagi, cha/xa, garnus, hola, matra, …) or 1 '
              'strong + 1 weak (ko, ma, …). Romaji tokens (onegaisamasu, nippori, …) are counted separately.',
              '- English-mix ratio = Latin English word units ÷ all word units (Burmese syllables via the sylbreak rule, '
              'Japanese characters ÷ 2, Devanagari/Latin words).',
              '- **Zawgyi heuristic** (Burmese): Zawgyi stores glyphs in visual order and reuses Myanmar code points, so '
              'some sequences are impossible in Unicode. Zawgyi signals: Z1 ေ (U+1031) not after a consonant/medial; Z2 '
              'U+103B before a consonant; Z3 any U+1060–U+1097; Z4 U+1039 not followed by a consonant; Z5 U+1004/U+101A '
              '+ U+1039; Z6 U+1033/U+1034. Unicode signals: U1 kinzi U+1004 U+103A U+1039 (×2); U2 U+103A after a '
              'consonant that never takes medial ya; U3 U+1037 next to U+103A; U4 medial then ေ (×0.5). p = Z/(Z+U): '
              '≥0.8 zawgyi, ≤0.2 unicode, else mixed; <4 Myanmar letters → none; no signal → uncertain.',
              f"- Self-test: {selftest[0]}/{selftest[1]} (4 Unicode strings, 4 hand-encoded Zawgyi strings, emoji, syllables, "
              "copy guard, romanized Nepali).",
              '- Limits: Shan/Mon/Karen text uses U+1060–U+109F legitimately (false Zawgyi); very short strings or '
              'hashtags carry no signal; mixed copy-pastes come out "mixed"; a screenshot cannot reveal the encoding '
              '(only rendering damage such as a dotted circle before ေ hints at Zawgyi), so screenshot-read Burmese is '
              'stored as transcribed Unicode with read_confidence < 1. Copied text (owner_paste) keeps the real bytes.',
              '', '## AI-likeness rubric', '',
              f"Score = clamp(sum of weights, 0, 1). AI-looking at ≥ {AI_THRESHOLD} (sample excluded / segment "
              f"ai_sounding); {BORDERLINE}–{AI_THRESHOLD} = borderline. Calibration: the {sum(1 for s in samples if s.get('text'))} "
              f"collected human captions score at most {human_max:.2f}; our v1 segments score "
              + ', '.join(f"{k} {min(v):.2f}–{max(v):.2f}" for k, v in seg.items()) + '.', '',
              '| signal | weight |', '|---|---|']
    lines += [f'| {k} | {v:+.2f} |' for k, v in WEIGHTS.items()]
    lines += ['', 'S = AI/template signals, H = human signals (typos, colloquial particles, natural code-mixing, '
                  'hand-typed spacing, local detail). S8 template repetition = ≥50% of a segment\'s lines recur '
                  '(codes/numbers/colours masked) in ≥80% of the 50 posts. S12 applies only because measured '
                  'Nepali-audience captions use no Devanagari body text. Analyst (Claude-coded) human markers on '
                  'competitor posts: hashtag typos (#swostikgaesha), non-native grammar, "Orginal".', '',
              '## Copy guard and fact guard', '',
              f"- No generated text (openings, CTAs, drafts, rewrites) may share a run of ≥6 words (URLs, codes and "
              f"prices masked), ≥20 Burmese characters or ≥12 Japanese characters with any collected text "
              f"({analysis['guard_corpus']['third_party_and_own_page_texts']} texts incl. cover overlays). Openings, CTAs "
              f"and drafts are additionally checked against every published v1 caption "
              f"({analysis['guard_corpus']['with_v1_published']} texts in total) so they are genuinely fresh. Result: "
              f"{sum(1 for c in ex if c['guard']['passed'])}/{len(ex)} passed.",
              f"- Drafts: no 細布, no width/length words, no supplier codes or supplier name, price ¥1,595 only, Messenger "
              f"link present, sold-out drafts start with [{DNP}], EN material only \"Hosonuno (100% cotton)\", JA only "
              f"綿100%, Burmese must classify as Unicode. Result: {sum(1 for c in analysis['content_checks']['drafts'] if not c['problems'])}"
              f"/{len(analysis['content_checks']['drafts'])} clean.",
              '- Rewrites of our 50 captions use only facts parsed from that row\'s published caption text (codes, '
              'price lines, sold-out codes, colour, tax/offer wording, Messenger link); a template needing a fact the row '
              'does not state is skipped. No width/length is used.', '',
              '## Measured vs model knowledge', '',
              '- `measured (n=…)` = counted from included human samples in fi_caption_samples; style rows in '
              'fi_caption_style with method=measured. A language with fewer than 10 included samples with body text is '
              '**provisional**.',
              '- `model_knowledge (not measured)` = typical small-seller practice from general knowledge, stored as '
              'method=model_knowledge rows (sample_size 0). Never mixed into measured counts.', '',
              '## Re-run (after screenshots arrive)', '', '```bash',
              'cd /var/www/bagisto-wt/ws-k   # or the live tree once merged',
              'D=/var/www/bagisto/storage/app/intel/captions',
              'python3 -I packages/Fujiba/AutoPoster/bin/intel/caption_style.py inbox --dir /root/incoming/caption-screens --out $D/inbox.json [--ocr]',
              'php packages/Fujiba/AutoPoster/bin/intel/caption_style_db.php export $D/bundle.json',
              'python3 -I packages/Fujiba/AutoPoster/bin/intel/caption_style.py analyse --bundle $D/bundle.json \\',
              '    --content docs/intelligence/caption-style-content.json --inbox $D/inbox.json --out $D/analysis.json',
              'python3 -I packages/Fujiba/AutoPoster/bin/intel/caption_style.py render --analysis $D/analysis.json \\',
              '    --content docs/intelligence/caption-style-content.json --outdir docs/intelligence',
              'php packages/Fujiba/AutoPoster/bin/intel/caption_style_db.php load $D/analysis.json [--dry-run]', '```',
              'Burmese/Nepali screenshots: Claude reads each image and writes `<image>.claude.txt` (first lines '
              '`# source: claude_read` and `# confidence: 0.x`) before `inbox` is run; en/ja screenshots get a tesseract '
              'draft `<image>.ocr.txt` (`--ocr`, heavy_guard) that must be checked and marked `# verified: yes`.',
              '', '## Limits (honest)', '',
              '- English and Nepali samples are short TikTok captions from Nepali-audience sellers of the same fabric; '
              'Facebook posts are longer by nature, so length comparisons are platform-skewed.',
              '- Burmese: zero competitor captions (Facebook not scrapable, no screenshots yet); the measured Burmese is '
              'our own page (1 seller). Japanese: zero samples.',
              '- Engagement is not exposed by oEmbed, so nothing here says which style sells better; that needs '
              'fi_performance / insights data later.']
    return '\n'.join(lines) + '\n'


def render_drafts(analysis, content, lang):
    L = content['languages'][lang]
    prods = {p['name']: p for p in analysis['draft_products']}
    chk = {(c['group'], c['n']): c for c in analysis['content_checks']['drafts'] if c['lang'] == lang}
    allok = all(c['guard']['passed'] and not c['problems'] for c in chk.values())
    lines = [f"# Caption drafts — {L['name']} ({lang}) — PREVIEW ONLY", '',
             '> Nothing here is posted or queued (no autoposter_queue rows). v1 captions are unchanged.',
             f"> Checks: copy guard + fact guard {'passed for every draft' if allok else 'FAILED (see analysis)'}"
             + ('; Burmese verified Unicode by the Zawgyi heuristic' if lang == 'my' else '') + '.', '',
             '**Facts used (product_flat, ' + analysis['bundle_generated_at'][:10] + ')** — nothing else:']
    for name in ('FV-67-A', 'FV-67-B', 'FV-67-C', 'FV-73-A', 'FV-83-A'):
        p = prods[name]
        state = 'in stock' if p['status'] == 1 and p['qty'] > 0 else 'SOLD OUT / disabled'
        lines.append(f"- {name} ({p['sku']}): colour {p['colour']}; pattern {p['pattern']}; ¥{int(p['price']):,}; {state}"
                     + (f" (qty {p['qty']} at export — re-check before posting)" if state == 'in stock' else ''))
    lines += [f"- Material wording allowed: EN \"Hosonuno (100% cotton)\", JA \"綿100%\", my/ne \"100% cotton\"; "
              f"never 細布. No width/length. No supplier codes. Messenger: {MESSENGER}",
              '- Shop facts reused from live v1 captions: cash on delivery, PayPay / card / bank transfer, delivery within Japan, 税込.',
              '']
    titles = {'FV-67': 'FV-67 series — A / B / C in stock',
              'FV-73-A': 'FV-73-A (KIMONO-2685) — SOLD OUT / disabled — ⛔ DO NOT POST until restocked',
              'FV-83-A': 'FV-83-A (KIMONO-2680) — SOLD OUT — ⛔ DO NOT POST until restocked'}
    for g in ('FV-67', 'FV-73-A', 'FV-83-A'):
        lines += [f'## {titles[g]}', '']
        for i, d in enumerate(L['drafts'][g], 1):
            c = chk[(g, i)]
            tag = 'guard ✓ facts ✓' if c['guard']['passed'] and not c['problems'] else 'CHECK FAILED: ' + '; '.join(c['problems'])
            if g != 'FV-67':
                lines.append(f'**{lang}-{g}-{i} — {DNP.upper()}** · {tag} · ai_score {c["ai_score"]}')
            else:
                lines.append(f'**{lang}-{g}-{i}** · {tag} · ai_score {c["ai_score"]}')
            lines += ['', '```text', d, '```', '']
    return '\n'.join(lines) + '\n'


def render_scores(analysis, content):
    sc = analysis['scores']
    by_q = collections.defaultdict(dict)
    for r in sc:
        by_q[r['queue_id']][r['segment_language']] = r
    n = len(by_q)
    any_ai = sum(1 for q in by_q.values() if any(r['ai_sounding'] for r in q.values()))
    per = {lang: (sum(1 for q in by_q.values() if lang in q and q[lang]['ai_sounding']),
                  sum(1 for q in by_q.values() if lang in q)) for lang in ('my', 'ne', 'ja', 'en', 'shared')}
    reasons = collections.Counter()
    for r in sc:
        if r['ai_sounding']:
            for s in r['reasons']['signals']:
                reasons[(r['segment_language'], s['code'])] += 1
    rng_ids = analysis['ours_range']
    lines = ['# v1 caption score — last 50 published posts', '',
             f"Posts {rng_ids['ids'][0]}–{rng_ids['ids'][1]}, published {rng_ids['published'][0]} → {rng_ids['published'][1]} "
             f"({_cs(collections.Counter(next(iter(q.values()))['kind'] for q in by_q.values()))} posts; collection kinds "
             f"come from CollectionPostBuilder, 'template' from the Premium templates). Rubric {METHOD_VERSION}: AI-sounding when "
             f"score ≥ {AI_THRESHOLD}. Same rubric scores the collected human captions at "
             f"{max((s['ai_likeness'] or 0) for s in analysis['samples'] if s.get('text')):.2f} max (calibration).", '',
             f"**{any_ai}/{n} posts have at least one AI-sounding language segment.**", '',
             '| segment | AI-sounding | posts with segment |', '|---|---|---|']
    for lang, (a, t) in per.items():
        lines.append(f'| {lang} | {a} | {t} |')
    lines += ['', '## Top reasons (segment · signal · posts)']
    plain = {'S1_em_dash': 'em dash', 'S2_middle_dot_chain': 'fragments chained with · / ・',
             'S3_telegraphic': 'noun stack with no verb (catalog voice)', 'S4_formal_register': 'formal/literary register',
             'S5_generic_praise_1': 'generic praise word', 'S5_generic_praise_2plus': 'generic praise words',
             'S6a_symmetric_emoji_frame': 'line framed by the same emoji', 'S6b_emoji_bullets': 'emoji-bullet lines',
             'S6c_flag_language_labels': 'flag-labelled translation line', 'S7_parallel_translation': 'same sentence translated',
             'S8_template_repetition': 'identical lines in >=80% of posts', 'S9_rhetorical_question_hook': 'rhetorical question',
             'S12_devanagari_body_vs_measured': 'Devanagari body (real sellers: none)'}
    for (lang, code), c in reasons.most_common(14):
        lines.append(f'- {lang} · {code} ({plain.get(code, code)}) · {c}')
    lines += ['', '## What is human already', '- ' + content['scores_notes']['human'], '',
              '## Example rewrites (staging only, status=proposed in fi_caption_scores; exact queue-row facts only)']
    shown = 0
    for qid in sorted(by_q, reverse=True):
        for lang in ('ne', 'ja', 'en'):
            r = by_q[qid].get(lang)
            if r and r['rewrite_suggestion'] and shown < 6 and (qid % 7 == 0 or shown < 3):
                lines += [f"**#{qid} {lang}** (score {r['ai_score']}) — before:", '```text', r['segment_text'], '```',
                          'after (proposal):', '```text', r['rewrite_suggestion'], '```', '']
                shown += 1
    lines += ['## Per post', '', '| queue id | published | kind | my | ne | ja | en |', '|---|---|---|---|---|---|---|']
    for qid in sorted(by_q, reverse=True):
        q = by_q[qid]
        any_r = next(iter(q.values()))

        def cell(lang):
            r = q.get(lang)
            return '–' if not r else (('**AI** ' if r['ai_sounding'] else 'ok ') + f"{r['ai_score']:.2f}")
        lines.append(f"| {qid} | {str(any_r['published_at'])[:16]} | {any_r['kind']} | {cell('my')} | {cell('ne')} | {cell('ja')} | {cell('en')} |")
    return '\n'.join(lines) + '\n'


def cmd_render(args):
    analysis = json.load(open(args.analysis, encoding='utf-8'))
    content = json.load(open(args.content, encoding='utf-8'))
    out = []
    for lang in LANGS:
        p = os.path.join(args.outdir, f'caption-style-{lang}.md')
        write_text(p, render_style(analysis, content, lang))
        out.append(p)
        p = os.path.join(args.outdir, f'caption-drafts-{lang}.md')
        write_text(p, render_drafts(analysis, content, lang))
        out.append(p)
    p = os.path.join(args.outdir, 'caption-scores-v1.md')
    write_text(p, render_scores(analysis, content))
    out.append(p)
    import contextlib
    import io
    with contextlib.redirect_stdout(io.StringIO()) as buf:
        cmd_selftest(None)
    m = re.search(r'selftest (\d+)/(\d+)', buf.getvalue())
    p = os.path.join(args.outdir, 'caption-style-method.md')
    write_text(p, render_method(analysis, content, (int(m.group(1)), int(m.group(2))) if m else (0, 0)))
    out.append(p)
    corpus = [(f"s{i}", x['text']) for i, x in enumerate(analysis['samples']) if x.get('text')]
    corpus += [(f"o{i}", x['overlay_text']) for i, x in enumerate(analysis['samples']) if x.get('overlay_text')]
    guard = CopyGuard(corpus)
    bad = 0
    for path in out:
        res = guard.check(open(path, encoding='utf-8').read())
        if not res['passed']:
            bad += 1
            print('DOC COPY-GUARD HIT', path, res['hits'][:3])
    for p in out:
        print(f'{os.path.getsize(p):>7}  {p}')
    print('doc-level copy guard vs collected texts:', 'passed' if not bad else f'{bad} doc(s) FAILED')
    return 1 if bad else 0


def cmd_check(args):
    analysis = json.load(open(args.analysis, encoding='utf-8'))
    corpus = [(s['fingerprint'][:12], s['text']) for s in analysis['samples'] if s.get('text')]
    corpus += [(s['fingerprint'][:12] + ':ov', s['overlay_text']) for s in analysis['samples'] if s.get('overlay_text')]
    res = CopyGuard(corpus).check(open(args.text_file, encoding='utf-8').read())
    print(json.dumps(res, ensure_ascii=False, indent=1))
    return 0 if res['passed'] else 1


# ------------------------------------------------------------ selftest
def cmd_selftest(_args):
    cases = [
        ('မင်္ဂလာပါရှင့်', 'unicode'),
        ('ကျေးဇူးတင်ပါတယ်', 'unicode'),
        ('ကြောင်းပါတယ်', 'unicode'),
        ('ဂျပန်ပြည်တွင်း&မြန်မာနိုင်ငံအိမ်အရောက်ပို့ပေးပါတယ်ရှင့်။', 'unicode'),
        ('ေက်းဇူးတင္ပါတယ္', 'zawgyi'),  # ေက်းဇူးတင္ပါတယ္
        ('မဂၤလာပါရွင့္', 'zawgyi'),                    # မဂၤလာပါရွင့္
        ('ေၾကာင္း', 'zawgyi'),                                                  # ေၾကာင္း
        ('\u1021\u102D\u1019\u1039\u1031\u101B\u102C\u1000\u1039\u1031\u1004\u103C\u1031\u1001\u103A\u1031\u1015\u1038', 'zawgyi'),  # အိမ္ေရာက္ေငြေခ်ေပး
        ('ab', 'none'),
    ]
    ok = 0
    for txt, want in cases:
        got = zawgyi_detect(txt)['label']
        ok += got == want
        print(f"{'ok ' if got == want else 'BAD'} zawgyi {want:8} got {got:9} {zawgyi_detect(txt)['z']} {zawgyi_detect(txt)['u']}")
    em = extract_emoji('🇯🇵 hi 🙏🏽 ❤️ 1️⃣ 👩‍👩‍👧 ♪ ✅')
    want_em = ['🇯🇵', '🙏🏽', '❤', '1⃣', '👩‍👩‍👧', '✅']
    ok += em == want_em
    print('ok ' if em == want_em else 'BAD', 'emoji', em)
    syl = my_syllables('ရှင့်ကျေးဇူးတင်ပါတယ်')
    ok += len(syl) == 6
    print('ok ' if len(syl) == 6 else 'BAD', 'syllables', syl)
    g = CopyGuard([('a', 'Original Japanese lungi available delivery all over japan order now'),
                   ('b', 'ကြိုက်တဲ့အရောင်လေး ရွေးပြီး Messenger မှာ မှာယူနိုင်ပါတယ်ရှင့်')])
    t1 = not g.check('lungi available delivery all over japan order today')['passed']
    t2 = g.check('Japanese lungi, delivery all over Japan')['passed']
    t3 = not g.check('Messenger မှာ မှာယူနိုင်ပါတယ်ရှင့် နော်')['passed']
    t4 = g.check('Messenger ကနေ မှာလို့ရပါတယ်နော်')['passed']
    for name, t in (('guard 6-word hit', t1), ('guard 5-word ok', t2), ('guard burmese 20 hit', t3), ('guard burmese ok', t4)):
        ok += t
        print('ok ' if t else 'BAD', name)
    a = analyse_text('Nippori koi rate ma Japani lungi matra :1700 for more information please inbox us')
    t5 = a['language'] == 'ne' and a['script'] == 'ne_roman'
    ok += t5
    print('ok ' if t5 else 'BAD', 'romanized nepali', a['language'], a['script'])
    total = len(cases) + 7
    print(f'selftest {ok}/{total}')
    return 0 if ok == total else 1


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = ap.add_subparsers(dest='cmd', required=True)
    p = sub.add_parser('inbox')
    p.add_argument('--dir', default='/root/incoming/caption-screens')
    p.add_argument('--out', required=True)
    p.add_argument('--ocr', action='store_true', help='run tesseract on en/ja screenshots without a sidecar (heavy_guard)')
    p = sub.add_parser('analyse')
    p.add_argument('--bundle', required=True)
    p.add_argument('--content', required=True)
    p.add_argument('--inbox')
    p.add_argument('--out', required=True)
    p = sub.add_parser('render')
    p.add_argument('--analysis', required=True)
    p.add_argument('--content', required=True)
    p.add_argument('--outdir', required=True)
    p = sub.add_parser('check')
    p.add_argument('--analysis', required=True)
    p.add_argument('--text-file', required=True)
    sub.add_parser('selftest')
    args = ap.parse_args()
    fn = {'inbox': cmd_inbox, 'analyse': cmd_analyse, 'render': cmd_render, 'check': cmd_check,
          'selftest': cmd_selftest}[args.cmd]
    sys.exit(fn(args) or 0)


if __name__ == '__main__':
    main()
