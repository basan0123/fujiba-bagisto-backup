#!/usr/bin/env python3
"""
Vertical 1080x1920 reel from a collection's stamped photos, in the style of the
page's own reels: blurred fabric background, slow zoom on the photo, white
size label (EN + Burmese) on top, FUJIBA end card. Silent track.

    post_reel.py <out.mp4> <label_en> <label_my> <end_my> <photo1> [photo2 ...]

Burmese text is drawn with ImageMagick pango: (ffmpeg drawtext cannot shape it).
"""
import os
import subprocess
import sys
import tempfile

SEG = 4.0      # seconds per photo
FADE = 0.5
FPS = 30
END = 3.0      # end card
INDIGO = '#141B33'
GOLD = '#D9B866'

# Reels shorter than this get throttled in the Reels feed, so a post with only
# one or two photos cycles back over them until it clears the floor rather than
# going out as a 5-second stub.
MIN_TOTAL = 20.0
MAX_PHOTOS = 8   # ceiling on the repeated pass, so a 1-photo post stays sane


def plan(photos):
    """Repeat the photo list until the reel clears MIN_TOTAL."""
    if not photos:
        return []

    seq = list(photos)
    while len(seq) < MAX_PHOTOS and (len(seq) * (SEG - FADE) + FADE + END) < MIN_TOTAL:
        seq.append(photos[len(seq) % len(photos)])

    return seq


def run(cmd):
    subprocess.run(cmd, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)


def pango(out, markup, bg='white', width=760):
    run(['convert', '-background', bg, '-size', f'{width}x', '-gravity', 'center',
         f'pango:{markup}', '-bordercolor', bg, '-border', '28x18', out])


def main():
    out, label_en, label_my, end_my, photos = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5:]

    # A/B arm: REEL_ENHANCE=1 applies a mild tone lift to the foreground photo
    # so enhanced vs untouched reels can be compared on reach/income. Kept
    # deliberately gentle — a previous heavy-handed enhance pass was rejected.
    enhance = os.environ.get('REEL_ENHANCE') == '1'
    fg_grade = ',eq=contrast=1.06:saturation=1.08:brightness=0.02,unsharp=5:5:0.6' if enhance else ''

    photos = plan(photos)

    tmp = tempfile.mkdtemp(prefix='reel')
    label = os.path.join(tmp, 'label.png')
    pango(label, f'<span font="Noto Sans Myanmar 40" foreground="#222">{label_en}\n{label_my}</span>')

    segs = []
    frames = int(SEG * FPS)
    for i, p in enumerate(photos):
        seg = os.path.join(tmp, f's{i}.mp4')
        # Slower zoom than the short-segment version: the same 1.10 ceiling over
        # a longer segment would otherwise drift visibly fast.
        zoom = f"zoompan=z='min(zoom+0.0006,1.10)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d={frames}:s=1080x1350:fps={FPS}"
        run(['ffmpeg', '-y', '-i', p, '-i', label, '-filter_complex',
             f"[0]scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,gblur=sigma=28,eq=brightness=-0.10,"
             f"loop=loop={frames - 1}:size=1:start=0,fps={FPS}[bg];"
             f"[0]scale=1440:1800{fg_grade},{zoom}[fg];[bg][fg]overlay=0:330:shortest=1[v];[v][1]overlay=(W-w)/2:120,format=yuv420p",
             '-frames:v', str(frames), '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', seg])
        segs.append(seg)

    end_png = os.path.join(tmp, 'end.png')
    run(['convert', '-size', '1080x1920', f'xc:{INDIGO}',
         '-font', '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf', '-fill', GOLD,
         '-gravity', 'center', '-pointsize', '150', '-annotate', '+0-220', 'FUJIBA',
         '-pointsize', '44', '-annotate', '+0-90', 'K I M O N O', end_png])
    end_txt = os.path.join(tmp, 'endtxt.png')
    pango(end_txt, f'<span font="Noto Sans Myanmar 38" foreground="#F4F1EA">{end_my}\nm.me/Fujiba Elegance Emporium</span>', bg=INDIGO, width=900)
    end = os.path.join(tmp, 'end.mp4')
    run(['ffmpeg', '-y', '-loop', '1', '-i', end_png, '-i', end_txt, '-filter_complex',
         '[0][1]overlay=(W-w)/2:1060,format=yuv420p', '-t', str(END), '-r', str(FPS),
         '-c:v', 'libx264', '-crf', '20', end])
    segs.append(end)

    # chain xfades
    args, chain, offset = [], '', 0.0
    for s in segs:
        args += ['-i', s]
    prev = '[0:v]'
    for i in range(1, len(segs)):
        offset += SEG - FADE
        chain += f'{prev}[{i}:v]xfade=transition=fade:duration={FADE}:offset={offset:.2f}[x{i}];'
        prev = f'[x{i}]'
    total = offset + END
    run(['ffmpeg', '-y', *args, '-f', 'lavfi', '-t', f'{total:.2f}', '-i', 'anullsrc=r=44100:cl=stereo',
         '-filter_complex', chain.rstrip(';'), '-map', prev, '-map', f'{len(segs)}:a',
         '-c:v', 'libx264', '-preset', 'medium', '-crf', '21', '-pix_fmt', 'yuv420p',
         '-c:a', 'aac', '-shortest', '-movflags', '+faststart', out])
    print(out)


if __name__ == '__main__':
    main()
