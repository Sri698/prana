# Build and sync script for prana_backend templates/index.html
import os

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
TEMPLATE_PATH = os.path.join(BASE_DIR, "templates", "index.html")
SOURCE_PATH = os.path.abspath(os.path.join(BASE_DIR, "..", "..", "prana_homeocare_fixed (5).html"))

if os.path.exists(SOURCE_PATH):
    with open(SOURCE_PATH, "r", encoding="utf-8") as f:
        html = f.read()
    with open(TEMPLATE_PATH, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"Successfully synced {len(html)} bytes from {os.path.basename(SOURCE_PATH)} to {TEMPLATE_PATH}")
else:
    print(f"Source file {SOURCE_PATH} not found!")
