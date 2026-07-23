"""Generate Lunamars web assets from the approved logo."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "public" / "images" / "brand"
LOGO_PATH = BRAND_DIR / "lunamars-logo.png"
SYMBOL_PATH = BRAND_DIR / "lunamars-symbol.png"
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


def create_favicon(symbol: Image.Image) -> None:
    symbol.save(
        FAVICON_PATH,
        format="ICO",
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)],
    )


def create_social_card(logo: Image.Image) -> None:
    width, height = 1200, 630
    card = Image.new("RGB", (width, height), (248, 250, 252))
    draw = ImageDraw.Draw(card)

    draw.rounded_rectangle((58, 56, 1142, 574), radius=42, fill=(255, 255, 255), outline=(226, 232, 240), width=2)
    draw.rounded_rectangle((58, 56, 78, 574), radius=10, fill=(112, 145, 0))

    max_logo_width = 760
    max_logo_height = 145
    logo_scale = min(max_logo_width / logo.width, max_logo_height / logo.height)
    social_logo = logo.resize(
        (round(logo.width * logo_scale), round(logo.height * logo_scale)),
        Image.Resampling.LANCZOS,
    )
    card.paste(social_logo, (126, 104), social_logo)
    draw.text(
        (126, 272),
        "Les services qui rapprochent.",
        font=font(39, bold=True),
        fill=(71, 85, 105),
    )
    draw.text(
        (126, 340),
        "Publiez un besoin. Comparez. Choisissez.",
        font=font(27),
        fill=(71, 85, 105),
    )

    badge = (126, 435, 766, 515)
    draw.rounded_rectangle(badge, radius=20, fill=(247, 244, 232))
    draw.text(
        (160, 458),
        "Particuliers  •  Professionnels  •  Partout",
        font=font(25, bold=True),
        fill=(91, 109, 0),
    )

    card.save(SOCIAL_CARD_PATH, optimize=True, quality=94)


if __name__ == "__main__":
    logo = Image.open(LOGO_PATH).convert("RGBA")
    symbol = Image.open(SYMBOL_PATH).convert("RGBA")
    create_favicon(symbol)
    create_social_card(logo)
    print(f"Favicon: {FAVICON_PATH}")
    print(f"Carte sociale: {SOCIAL_CARD_PATH}")
