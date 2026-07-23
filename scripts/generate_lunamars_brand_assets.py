"""Generate the typographic Lunamars favicon and social-sharing card."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
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


def create_favicon() -> None:
    canvas = Image.new("RGBA", (512, 512), (79, 70, 229, 255))
    draw = ImageDraw.Draw(canvas)
    letter_font = font(330, bold=True)
    bounds = draw.textbbox((0, 0), "L", font=letter_font)
    text_width = bounds[2] - bounds[0]
    text_height = bounds[3] - bounds[1]
    draw.text(
        ((512 - text_width) / 2 - bounds[0], (512 - text_height) / 2 - bounds[1] - 8),
        "L",
        font=letter_font,
        fill=(255, 255, 255, 255),
    )
    canvas.save(
        FAVICON_PATH,
        format="ICO",
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)],
    )


def create_social_card() -> None:
    width, height = 1200, 630
    card = Image.new("RGB", (width, height), (248, 250, 252))
    draw = ImageDraw.Draw(card)

    draw.rounded_rectangle((58, 56, 1142, 574), radius=42, fill=(255, 255, 255), outline=(226, 232, 240), width=2)
    draw.rounded_rectangle((58, 56, 78, 574), radius=10, fill=(79, 70, 229))

    draw.text((126, 116), "Lunamars", font=font(82, bold=True), fill=(30, 41, 59))
    draw.text(
        (126, 238),
        "Les services qui rapprochent.",
        font=font(42, bold=True),
        fill=(79, 70, 229),
    )
    draw.text(
        (126, 316),
        "Publiez un besoin. Comparez. Choisissez.",
        font=font(29),
        fill=(71, 85, 105),
    )

    badge = (126, 424, 766, 504)
    draw.rounded_rectangle(badge, radius=20, fill=(238, 242, 255))
    draw.text(
        (160, 447),
        "Particuliers  •  Professionnels  •  Partout",
        font=font(25, bold=True),
        fill=(67, 56, 202),
    )

    card.save(SOCIAL_CARD_PATH, optimize=True, quality=94)


if __name__ == "__main__":
    create_favicon()
    create_social_card()
    print(f"Favicon: {FAVICON_PATH}")
    print(f"Carte sociale: {SOCIAL_CARD_PATH}")
