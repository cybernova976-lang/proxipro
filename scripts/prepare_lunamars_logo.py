"""Prepare transparent Lunamars logo assets from the approved source image."""

from __future__ import annotations

import argparse
from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "public" / "images" / "brand"
LOGO_PATH = BRAND_DIR / "lunamars-logo.png"
SYMBOL_PATH = BRAND_DIR / "lunamars-symbol.png"


def remove_neutral_background(source: Image.Image) -> Image.Image:
    rgba = source.convert("RGBA")
    cleaned = Image.new("RGBA", rgba.size, (0, 0, 0, 0))
    source_pixels = rgba.load()
    target_pixels = cleaned.load()

    for y in range(rgba.height):
        for x in range(rgba.width):
            red, green, blue, alpha = source_pixels[x, y]
            chroma = max(red, green, blue) - min(red, green, blue)

            if chroma <= 7:
                target_pixels[x, y] = (red, green, blue, 0)
            else:
                edge_alpha = max(0, min(255, round((chroma - 7) * 255 / 53)))

                if edge_alpha >= 250:
                    target_pixels[x, y] = (red, green, blue, alpha)
                    continue

                normalized_alpha = edge_alpha / 255
                restored_channels = []
                for channel in (red, green, blue):
                    restored = (channel - 255 * (1 - normalized_alpha)) / normalized_alpha
                    restored_channels.append(max(0, min(255, round(restored))))

                target_pixels[x, y] = (*restored_channels, edge_alpha)

    return cleaned


def crop_with_padding(image: Image.Image, padding: int) -> Image.Image:
    bounds = image.getchannel("A").getbbox()
    if bounds is None:
        raise RuntimeError("Le logo ne contient aucun pixel visible.")

    left = max(0, bounds[0] - padding)
    top = max(0, bounds[1] - padding)
    right = min(image.width, bounds[2] + padding)
    bottom = min(image.height, bounds[3] + padding)
    return image.crop((left, top, right, bottom))


def create_symbol(source: Image.Image) -> Image.Image:
    left_area = source.crop((0, 0, round(source.width * 0.25), source.height))
    symbol = crop_with_padding(left_area, 10)
    side = max(symbol.width, symbol.height)
    canvas = Image.new("RGBA", (side, side), (0, 0, 0, 0))
    canvas.alpha_composite(symbol, ((side - symbol.width) // 2, (side - symbol.height) // 2))
    return canvas.resize((512, 512), Image.Resampling.LANCZOS)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("source", type=Path, help="Capture source approuvée par le propriétaire")
    args = parser.parse_args()

    source = Image.open(args.source)
    cleaned = remove_neutral_background(source)
    logo = crop_with_padding(cleaned, 14)
    symbol = create_symbol(cleaned)

    BRAND_DIR.mkdir(parents=True, exist_ok=True)
    logo.save(LOGO_PATH, optimize=True)
    symbol.save(SYMBOL_PATH, optimize=True)

    print(f"Logo horizontal: {LOGO_PATH} ({logo.width}x{logo.height})")
    print(f"Symbole: {SYMBOL_PATH} ({symbol.width}x{symbol.height})")


if __name__ == "__main__":
    main()
