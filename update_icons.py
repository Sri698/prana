import os
from PIL import Image

white_master = Image.open('master_icon_white.png')
trans_master = Image.open('master_icon_trans.png')

# 1. Update assets/app-icon.png
app_icon = white_master.resize((512, 512), Image.Resampling.LANCZOS)
app_icon.save('assets/app-icon.png', 'PNG')
print('Updated assets/app-icon.png (512x512)')

# 2. Android mipmap sizes
densities = {
    'mipmap-mdpi': (48, 108),
    'mipmap-hdpi': (72, 162),
    'mipmap-xhdpi': (96, 216),
    'mipmap-xxhdpi': (144, 324),
    'mipmap-xxxhdpi': (192, 432)
}

res_dir = os.path.join('android', 'app', 'src', 'main', 'res')

for folder, (std_size, fg_size) in densities.items():
    fdir = os.path.join(res_dir, folder)
    os.makedirs(fdir, exist_ok=True)

    # Standard launcher icon (solid white background)
    std_img = white_master.resize((std_size, std_size), Image.Resampling.LANCZOS)
    std_img.save(os.path.join(fdir, 'ic_launcher.png'), 'PNG')
    std_img.save(os.path.join(fdir, 'ic_launcher_round.png'), 'PNG')

    # Adaptive foreground (transparent background)
    fg_img = trans_master.resize((fg_size, fg_size), Image.Resampling.LANCZOS)
    fg_img.save(os.path.join(fdir, 'ic_launcher_foreground.png'), 'PNG')
    print(f'Updated {folder}: std={std_size}x{std_size}, fg={fg_size}x{fg_size}')

# 3. Update ic_launcher_background.xml to #FFFFFF
bg_xml_path = os.path.join(res_dir, 'values', 'ic_launcher_background.xml')
with open(bg_xml_path, 'w', encoding='utf-8') as f:
    f.write('<?xml version="1.0" encoding="utf-8"?>\n<resources>\n    <color name="ic_launcher_background">#FFFFFF</color>\n</resources>\n')
print('Updated ic_launcher_background.xml to #FFFFFF')
