# AI try-on / image-to-video providers taking a fabric or garment reference

Research date 2026-10-14. Sources are official pricing and model pages only. Nothing was tested:
no signups, no API calls. Prices are list prices in USD and change often, so check the page before any spend.

| Provider | Model | Per image | Per second of video | Garment/pattern reference? | Pricing page |
|---|---|---|---|---|---|
| Google (Gemini API) | Nano Banana 2 (Gemini 3.1 Flash Image) / Nano Banana Pro (Gemini 3 Pro Image) | $0.067 (1K), $0.101 (2K), $0.151 (4K) / $0.134 (1K-2K), $0.24 (4K). 2.5 Flash Image is $0.039 but shut down 2026-10-02 | Veo 3.1: Standard $0.40 (720p/1080p), Fast $0.12 (1080p), Lite $0.08 (1080p) | Yes, as general reference images (multi-image editing; Veo takes reference images). Not a dedicated try-on model | https://ai.google.dev/gemini-api/docs/pricing |
| FASHN | Try-On v1.6 (also Product-to-Model, Image-to-Video endpoints) | 1 credit = $0.075 on demand ($0.049-$0.0675 on commitment tiers) | Not published for the API | Yes: dedicated try-on. Accepts flat-lay, ghost-mannequin or on-model product photos | https://help.fashn.ai/plans-and-pricing/api-pricing |
| fal.ai | Kling Kolors Virtual Try-On v1.5 (DEPRECATED; fal points to leffa/virtual-tryon and image-apps-v2/virtual-try-on) | $0.07 per generation | Kling v2.5 Turbo Pro i2v $0.07; Kling v3 Pro i2v $0.14 | Yes: garment_image_url (prefers a clean flat lay) | https://fal.ai/pricing , https://fal.ai/models/fal-ai/kling/v1-5/kolors-virtual-try-on |
| Replicate | IDM-VTON (cuuupid/idm-vton) | about $0.024 per run (A100, about 17 s) | n/a | Yes: garment image. **License CC BY-NC-SA 4.0, non-commercial only, so we cannot use it for Fujiba ads** | https://replicate.com/cuuupid/idm-vton |
| Runway API | gen4_turbo / gen4.5 / kling2.5_turbo_pro / seedance2_5 and others; image: gpt_image_2_5, ideogram_4_5, grok_imagine_image_2 | $0.02-$0.25 per image depending on model and quality; about +$0.01 per reference image | gen4_turbo $0.05, gen4.5 $0.12, kling2.5 $0.12, seedance2_5 $0.20-$0.68 | Yes, as reference images or start frames (general purpose, not try-on) | https://docs.dev.runwayml.com/guides/pricing/ |
| Kling (direct) | No separate price taken; Kling i2v pricing above is from fal and Runway | - | $0.07-$0.14 (fal) | Start-frame image | (see the fal and Runway rows) |

## Pattern fidelity (from docs and papers, untested)
- No provider page above promises exact print or pattern fidelity. fal's Kolors page says only "fabric texture, color, and fit", and recommends simple silhouettes and testing busy patterns. FASHN's pages don't cover print fidelity at all.
- Diffusion try-on models (IDM-VTON, Kolors, FASHN, Leffa) regenerate the garment pixels. Small, dense, repeating kimono motifs and gold metallic detail are where they tend to drift: motifs get re-drawn, the scale changes, and the repeat stops matching. IDM-VTON's paper claims better detail preservation through its garment encoder, but it still synthesises the pixels rather than copying them.
- General image and video models (Gemini/Nano Banana, Veo, Runway, Kling) only use the reference as guidance. Expect pattern drift to grow over the length of a video clip.
- **For Fujiba:** none of these outputs would pass our fabric_gate ("fabric is sacred": real pixels only). Any AI worn or try-on image needs to be labelled and kept out of the gated pipeline, or used only for silhouette while the real fabric crops are composited on top. Owner approval and a sample review are required before any paid call.
