# Starter Site images

AJNanda's section patterns ship **no photos** — every visual is a soft
placeholder card that reads "Replace this placeholder… (use the Image
block)". This is deliberate: the theme carries no bundled image assets, and
a Starter Site import writes plain block markup, so there is nothing to
license, ship, or keep in sync.

That also means the theme cannot *generate* images for you. If a starter
should launch with real (or AI-generated) imagery, the workflow is:

1. Import the starter (`AJNanda → Starter Sites`, or
   `wp ajnanda starter import <slug>`).
2. Generate the images you want — see the per-starter prompt sets below —
   at roughly **1600×1000** for hero/feature images and **800×800** for
   portraits and team photos. Ask for realistic/photographic results, not
   illustration or 3D-render styles.
3. Open each page, select the placeholder card, replace it with an **Image**
   block, and upload. Set meaningful alt text.
4. For team grids, replace each of the four photo placeholders.

If you would rather the import place the images automatically, that needs a
small build (an `images/starter/` asset dir + an importer pass that swaps
the placeholder card for a real Image block when a matching file exists,
falling back to the card when it does not). Not built yet — request it and
hand over a folder of files named per the slots below.

The prompts below are starting points — adjust the person's appearance,
setting, and palette to match the real practice or individual. Keep a
consistent lens, lighting, and grade across a starter so the set looks like
one shoot.

---

## `nanda-dentist` — "Nanda - Dentist" (palette: Corporate Blue)

| Page | Slot | Prompt |
|---|---|---|
| Home | Hero (split) | "A bright modern dental office reception, warm daylight, soft blue accents, a friendly receptionist, shallow depth of field, editorial photography" |
| Home | — | (stats, testimonials, CTA are text-only) |
| About Us | Story (image right) | "Two dentists in clean scrubs talking in a sunlit treatment room, candid, natural light, calm and reassuring" |
| About Us | Team grid ×4 | "Head-and-shoulders studio portrait of a dental professional against a light grey seamless backdrop, soft key light, friendly, neutral expression" (vary age, gender, attire) |
| Services | (card grid / rows are icon + text) | Optional: "Close-up of a dental hygienist's gloved hands with modern instruments, macro, soft focus background" |
| New Patients | Content (image left) | "A patient at a tablet completing intake forms in a comfortable waiting area with plants and warm wood tones" |
| Smile Gallery | Feature grid | Before/after smile close-ups, evenly lit, neutral background — one per tile |

## `aad-astrophysicist` — "Aad - Astrophysicist" (palette: Deep Space — violet on dark)

| Page | Slot | Prompt |
|---|---|---|
| Home | Hero (split) | "Environmental portrait of an astrophysicist beside a large telescope dome at dusk, deep violet and indigo sky, subtle starlight, cinematic, 85mm" |
| Home | — | (statement, research areas, stats, quote, CTA are text-only) |
| Research | (rows / feature grid are text) | Optional per row: "Abstract scientific visualization — a simulated galaxy merger / gravitational lensing / radio-telescope array — dark background, violet-magenta data palette" |
| Talks & Media | Feature (image right) | "A speaker on a dark conference stage under a projected starfield, mid-gesture, dramatic rim light, audience silhouettes" |
| Bio | Story (image right) | "Candid photo of a researcher at a whiteboard covered in equations, warm desk lamp, dark room, thoughtful" |
| Notes | (blog landing — post thumbnails come from posts) | — |

## `viraj-orthopedic` — "Viraj - Orthopedic" (palette: Kinetic Clinic — fresh green)

| Page | Slot | Prompt |
|---|---|---|
| Home | Hero (split) | "An orthopedic surgeon in green scrubs reviewing a knee X-ray on a lightbox in a bright modern clinic, confident, clean, natural light" |
| Home | — | (conditions grid, recovery steps, stats, stories, CTA are text-only) |
| About Dr. Viraj | (about-professional: comparison, credentials, leadership are text) | Optional: "Professional portrait of a surgeon in a white coat, arms crossed, bright clinic corridor background, approachable" |
| Conditions & Treatments | (card grid / rows are icon + text) | Optional per row: "Clean anatomical illustration of a knee / shoulder / hip joint, green and slate palette, white background, medical-editorial style" |
| Sports Medicine | Hero + feature (image right) | "An athlete doing supervised rehab with a physiotherapist on a clinic floor, motion, green accent equipment, energetic natural light" |
| For Patients | Content (image left) | "A patient and clinician reviewing a recovery plan on a tablet, bright consult room, reassuring" |
| Our Care Team | Team grid ×4 | "Studio portrait of a clinician against a soft green-grey backdrop, friendly, natural light" (vary role: surgeon, PA, physiotherapist, nurse) |
| Patient Stories | (testimonial cards / featured quote are text) | Optional: candid photos of active people — hiking, cycling, playing with kids — bright and hopeful |

## `raunak-virologist` — "Raunak - Virologist" (palette: Lab Fluoro — magenta on near-black)

| Page | Slot | Prompt |
|---|---|---|
| Home | Hero (split) | "Environmental portrait of a virologist in PPE at a biosafety cabinet, magenta and cyan lab lighting, near-black background, cinematic, 85mm" |
| Home | — | (statement, research areas, stats, quote, CTA are text-only) |
| Research | (rows / feature grid are text) | Optional per row: "Fluorescence-microscopy image of infected cells — magenta nuclei, cyan cytoplasm, green viral protein — on black, confocal style" |
| The Lab | Team grid ×4 | "Studio portrait of a lab researcher in a white coat against a dark charcoal backdrop, subtle magenta rim light, focused" (vary role: PI, postdoc, technician, PhD student) |
| Outbreak Response | (steps / metrics / capability grid are text) | Optional: "A gloved hand loading a sequencing flow cell, shallow focus, magenta instrument glow, dark lab" |
| Talks & Media | Feature (image right) | "A scientist presenting outbreak data on a large dark screen, magenta charts, lecture-hall silhouettes, dramatic light" |
| About | Story (image right) | "Candid photo of a researcher reviewing genome data on two monitors in a dim office, magenta screen glow, thoughtful" |
| Field Notes | (blog landing — thumbnails come from posts) | — |

---

## If you want a starter to ship images by default

Nothing in the current system supports that. It would mean adding an
`images/starter/<slug>/` directory of real asset files to the theme and a
page-design variant that references them with `wp:image` blocks pointing at
`get_template_directory_uri()`. That is a theme-weight and licensing
decision — raise it explicitly before building it.
