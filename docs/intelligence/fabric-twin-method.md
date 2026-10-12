# Fabric digital twin: method (WS-L, 2026-10-12)

**Goal.** Render the exact print of each fabric (110 × 200 cm) for ads, stories, mock wearing videos and live selling, so it matches the real product. **Rule:** every fabric pixel comes from a real photo pixel through deterministic geometry only: crop, rotation, affine/homography, and scaling with standard interpolation. We never use inpainting, synthesis, AI upscaling, "heal/clone" seam fixing, median-stacking, de-wrinkling or watermark removal. The common "Offset → heal the seam" tile recipe ([Kost](https://jkost.com/blog/2015/01/how-to-create-a-seamless-pattern-tile-in-photoshop.html)) is used only to *inspect* seams, never to paint them. Supplier photos are internal reference only, for matching and colour. They are never a tile source.

## 1. Capture (what the industry does, and what we ask the owner to do)
- **Flatbed or controlled camera.** Browzwear asks for flatbed scans of at least 600 dpi with uniform light, no wrinkles or folds, and *"the full repeat in all directions must be present"* ([Browzwear](https://help.browzwear.com/en/articles/13065665-capturing-fabric-images)). CLO's steps are scan, make repeat, match DPI, then adjust colour; a phone is acceptable when it is held parallel with the grain aligned to the frame ([CLO support](https://support.clo3d.com/hc/en-us/articles/115012228067)). CLO's paid digitising service wants swatches of at least 50 × 50 cm with warp/weft marked ([CLO service](https://www.clo3d.com/en/enterprise/fabric-service)). Vizoo xTex (up to 1200 dpi, multi-light) finds the repeat automatically for wovens and prints ([Digital Production](https://digitalproduction.com/2021/02/24/xtex-nahtlose-texturen/)). A 2 m Fujiba piece is far larger than any flatbed, so we use an overhead phone shot of the whole width, with the phone parallel to the fabric.
- **Cross-polarised light** (polariser on the light and on the lens, at 90°) removes specular glare from shiny prints and gold foil. That gives diffuse colour only ([Flood](https://derekflood.powweb.com/renderdocsDir/photography/xpol.html), [Sketchfab](https://sketchfab.com/blogs/community/capturing-reflective-objects-in-3d)). It is optional and an owner purchase decision.
- **Colour.** Put a ColorChecker (24 patches) in the same light as the fabric. It should be flat, parallel to the sensor, cover 5–10 % of the frame, show no glare, and be shot in RAW ([Calibrite](https://calibrite.com/us/know-your-chart-a-color-perfectionists-guide-to-the-24-patch-colorchecker-classic/)). Calibrite's software builds DNG/ICC camera profiles, and its grey patch sets white balance ([Calibrite Passport](https://www.digitalcamerawarehouse.com.au/calibrite-colourchecker-passport-photo-2)). Adobe's DNG Profile Editor builds a .dcp profile from a 24-patch chart ([guide](https://photography.tutsplus.com/tutorials/how-to-create-a-custom-color-profile-for-your-camera--cms-24339), [E. Chan](http://people.csail.mit.edu/ericchan/dp/acr/)). Without a card, plain white paper fixes white balance but not the full colour transform.
- **Scale.** A ruler or tape in frame gives cm per pixel, which turns the measured repeat into `repeat_size_cm`. Without it, the repeat stays in pixels and the 110 × 200 cm render scale is an assumption.

## 2. Tile = exactly one repeat, measured, never invented
1. **Clean region.** Fabric mask (backdrop = smooth neutral pixels connected to the frame). We remove OCR'd text, the sticker and label, the template-detected "Fujiba Elegance Emporium" overlay, and long internal straight lines (folds, laps, package edges). Then we take the maximal clean rectangles.
2. **Lattice.** Masked normalised auto-correlation in L\*a\*b\* via FFT ([Padfield 2012](https://simpleitk.org/doxygen/latest/html/classitk_1_1simple_1_1MaskedFFTNormalizedCorrelationImageFilter.html)). Repeats are found as autocorrelation peaks (Lin et al. 1997, via [Han & McKenna](https://www.scitepress.org/Papers/2009/17927/17927.pdf); [Patch redundancy](https://arxiv.org/pdf/1904.06428)). The shortest vector within 5 % of the best peak becomes the basis. That rejects sub-periods where the motif shape repeats but the colour does not. Vectors are refined at full resolution, and the rectangular super-cell (rotary screens repeat rectangularly; half-drop gives W × 2H) defines the tile.
3. **Gates** (pre-registered in `twin_common.py`, commit 97ecf19, before any catalog measurement):
   - lattice NCC ≥ 0.80 on both vectors;
   - seam CIEDE2000 mean ≤ 2.0 and p95 ≤ 5.0 (σ 1.5 blur; [Sharma et al.](https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/)), and 1 − NCC(L\*) ≤ 0.10, on both wrap seams. Each seam compares the tile edge with the real fabric one period away;
   - artefacts ≤ 0.5 % of tile pixels may differ by dE00 > 10 from every lattice copy. This catches non-periodic content such as watermarks, stickers and glare;
   - rotation and skew ≤ 0.5° count as a plain crop.
4. **Fixes, all labelled in `fi_twin_attempts`; any fix caps the tier at B.**
   - Rotate/deskew via the lattice.
   - Homography from the two straight fabric edges.
   - Illumination flattening (divide by the luminance averaged over one repeat window, which cannot contain the print). This changes pixel values, so it is a gated colour correction: only allowed when the gain stays within ±15 %.
5. **Tiers.**
   - **A**: own photo, sharp, all gates pass with a plain crop, cast ≤ 6, lighting evenness ≥ 0.60.
   - **B**: passes only after a fix, or colour is not reliable.
   - **C**: no gated tile, so a new shoot is needed.
   - Legacy uploads (800 × 800 .jpg) are rejected if SIFT plus post-alignment NCC ≥ 0.92 shows they are the supplier's photograph.

## 3. Rendering and proof
- **Flat 110 × 200 cm @150 dpi** (6496 × 11811 px). The tile is scaled once (Lanczos) to the stated cm-per-repeat, then tiled by integer copy in uint8 strips. There are no float full frames, and the job takes the heavy lock with a 3 GB cap.
- **Ads, story and video pan** are `cover(crop_box(render))` crops with a sidecar, checked by `fabric_gate.py --master`. That check rebuilds pixels from the same render, so it is **circular**: it proves the composition pipeline, not identity.
- **Identity proof is independent.** The render is registered to an untouched photo with SIFT/ORB plus RANSAC (`fabric_match.py`), then dE00 is measured on matched regions. Held-out areas the tile was not cut from must also match.
- **Garments.** The cloth industry drapes with simulation: mass, tension/compression/shear/bending stiffness, damping, collisions, pinning ([Blender cloth](https://docs.blender.org/manual/en/latest/physics/cloth/settings/physical_properties.html)), with UV maps at real texel density ([Blender UV](https://docs.blender.org/manual/en/latest/modeling/meshes/editing/uv.html)). Blender is **not installed** (an owner decision). Until then, lungi and worn views are a deterministic 2-D UV/displacement warp with a declared multiplicative shading layer, labelled *"procedural mock, not photoreal"*.

## 4. Limits
- **Colour.** No product photo has a colour card, so A means "as photographed" (white balance checked on neutral pixels, evenness measured), not colorimetrically exact.
- **Scale.** No ruler, so `repeat_size_cm` stays NULL and 110 × 200 cm renders use a stated, assumed scale.
- **Watermark detection** catches about half of overlays at zero false alarms on our calibration set. The artefact gate, not the detector, is what keeps overlays out of tiles.
- **Coverage.** Most prints repeat at a larger scale than any existing photo shows. Only a new flat, full-width shot of at least 1.2 m (two repeats each way), with card and ruler, makes them exact.
