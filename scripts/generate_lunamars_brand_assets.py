"""Generate deterministic web assets from the approved Lunamars logo mark."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "public" / "images" / "brand"
MARK_PATH = BRAND_DIR / "lunamars-mark.png"
SOCIAL_CARD_PATH = ROOT / "public" / "images" / "social-card.png"
FAVICON_PATH = ROOT / "public" / "favicon.ico"


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        Path("C:/Windows/Fonts/seguisb.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf"),
        Path("C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf"),
    ]

    for candidate in candidates:
        if candidate.exists():
            return ImageFont.truetype(str(candidate), size)

    return ImageFont.load_default()


def prepare_mark() -> Image.Image:
    source = Image.open(MARK_PATH).convert("RGBA")
    alpha_bounds = source.getchannel("A").getbbox()

    if alpha_bounds is None:
        raise RuntimeError("Le logo Lunamars ne contient aucun pixel visible.")

    cropped = source.crop(alpha_bounds)
    side = max(cropped.size)
    padding = max(32, round(side * 0.10))
    canvas_side = side + padding * 2
    canvas = Image.new("RGBA", (canvas_side, canvas_side), (0, 0, 0, 0))
    canvas.alpha_composite(
        cropped,
        ((canvas_side - cropped.width) // 2, (canvas_side - cropped.height) // 2),
    )

    prepared = canvas.resize((1024, 1024), Image.Resampling.LANCZOS)
    prepared.save(MARK_PATH, optimize=True)
    return prepared


def create_favicon(mark: Image.Image) -> None:
    mark.save(
        FAVICON_PATH,
        format="ICO",
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)],
    )


def create_social_card(mark: Image.Image) -> None:
    width, height = 1200, 630
    card = Image.new("RGB", (width, height))
    pixels = card.load()

    start = (7, 21, 47)
    end = (49, 46, 129)
    for y in range(height):
        ratio = y / max(1, height - 1)
        row_color = tuple(round(start[i] * (1 - ratio) + end[i] * ratio) for i in range(3))
        for x in range(width):
            pixels[x, y] = row_color

    draw = ImageDraw.Draw(card, "RGBA")
    draw.ellipse((850, -210, 1320, 260), fill=(58, 134, 255, 26))
    draw.ellipse((800, 365, 1270, 835), fill=(124, 58, 237, 32))

    logo_panel = (72, 86, 342, 356)
    draw.rounded_rectangle(logo_panel, radius=56, fill=(255, 255, 255, 242))
    mark_for_card = mark.resize((226, 226), Image.Resampling.LANCZOS)
    card.paste(mark_for_card, (94, 108), mark_for_card)

    draw.text((390, 112), "Lunamars", font=font(76, bold=True), fill=(255, 255, 255))
    draw.text(
        (390, 218),
        "Les services qui rapprochent.",
        font=font(35, bold=True),
        fill=(216, 224, 255),
    )
    draw.text(
        (390, 278),
        "Publiez un besoin. Comparez. Choisissez.",
        font=font(25),
        fill=(198, 211, 244),
    )

    badge = (72, 430, 704, 516)
    draw.rounded_rectangle(badge, radius=24, fill=(255, 255, 255, 24), outline=(255, 255, 255, 42), width=2)
    draw.text(
        (106, 454),
        "Particuliers  •  Professionnels  •  Partout",
        font=font(25, bold=True),
        fill=(255, 255, 255),
    )

    card.save(SOCIAL_CARD_PATH, optimize=True, quality=94)


if __name__ == "__main__":
    BRAND_DIR.mkdir(parents=True, exist_ok=True)
    prepared_mark = prepare_mark()
    create_favicon(prepared_mark)
    create_social_card(prepared_mark)
    print(f"Logo: {MARK_PATH}")
    print(f"Favicon: {FAVICON_PATH}")
    print(f"Carte sociale: {SOCIAL_CARD_PATH}")
