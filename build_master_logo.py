import os
import shutil
from PIL import Image, ImageDraw, ImageFont
import numpy as np

# Load high quality cropped emblem
emblem = Image.open('assets/prana_emblem_hq.png').convert('RGBA')
# Crop halo to solid base: y from 8 to 232 -> (emblem.width x 224)
emblem_c = emblem.crop((0, 8, emblem.width, 232))

canvas = Image.new('RGBA', (820, 224), (255, 255, 255, 0))

# 1. Paste emblem at x=6, y=0
canvas.paste(emblem_c, (6, 0), emblem_c)

# 2. Draw vertical divider line at x=246, from y=14 to y=210
draw = ImageDraw.Draw(canvas)
gold_color = (206, 162, 65, 255) # Rich warm gold (#CEA241)
draw.line([(246, 14), (246, 210)], fill=gold_color, width=4)

# 3. Letters for PRANA
lp = Image.open('assets/letter_P.png').convert('RGBA')
lr = Image.open('assets/letter_R.png').convert('RGBA')
la = Image.open('assets/yogic_a.png').convert('RGBA')
ln = Image.open('assets/letter_N.png').convert('RGBA')

s = 1.14
def rescale(im):
    return im.resize((int(im.width * s), int(im.height * s)), Image.Resampling.LANCZOS)

p_s = rescale(lp)
r_s = rescale(lr)
a1_s = rescale(la)
n_s = rescale(ln)
a2_s = rescale(la)

baseline = 130
cur_x = 270
gap = 9

canvas.paste(p_s, (cur_x, baseline - p_s.height), p_s)
cur_x += p_s.width + gap
canvas.paste(r_s, (cur_x, baseline - r_s.height), r_s)
cur_x += r_s.width + gap
canvas.paste(a1_s, (cur_x, baseline - a1_s.height), a1_s)
cur_x += a1_s.width + gap
canvas.paste(n_s, (cur_x, baseline - n_s.height), n_s)
cur_x += n_s.width + gap
canvas.paste(a2_s, (cur_x, baseline - a2_s.height), a2_s)
end_x = cur_x + a2_s.width
prana_w = end_x - 270

# 4. HOMEO CARE (Strong, crisp, bolder font)
font_h = ImageFont.truetype(r'C:\Windows\Fonts\segoeuib.ttf', 33)
homeo_text = 'HOMEO CARE'
h_bbox = draw.textbbox((0, 0), homeo_text, font=font_h)
hw = h_bbox[2] - h_bbox[0]
hh = h_bbox[3] - h_bbox[1]

homeo_x = 270 + (prana_w - hw) // 2
homeo_y = 141

# Golden flanking rules (width 3 for crisp visibility at navbar size)
rule_y = homeo_y + hh // 2 + 2
draw.line([(270, rule_y), (homeo_x - 14, rule_y)], fill=gold_color, width=3)
draw.line([(homeo_x + hw + 14, rule_y), (end_x, rule_y)], fill=gold_color, width=3)

text_color = (7, 30, 34, 255) # Deep clinical dark
draw.text((homeo_x, homeo_y), homeo_text, fill=text_color, font=font_h)

# 5. DOOR OF HOPE (Bold, dark, clearly legible)
font_d = ImageFont.truetype(r'C:\Windows\Fonts\segoeuib.ttf', 23)
door_text = 'D O O R   O F   H O P E'
d_bbox = draw.textbbox((0, 0), door_text, font=font_d)
dw = d_bbox[2] - d_bbox[0]

door_x = 270 + (prana_w - dw) // 2
door_y = 188
door_color = (20, 48, 54, 255) # Strong high contrast
draw.text((door_x, door_y), door_text, fill=door_color, font=font_d)

# Crop canvas to exact content
arr = np.array(canvas)
non_w = arr[:, :, 3] > 10
y_idx, x_idx = np.where(non_w)
final_logo = canvas.crop((x_idx.min(), y_idx.min(), x_idx.max() + 1, y_idx.max() + 1))

# Save master outputs
final_logo.save('assets/prana_brand_logo.png')
final_logo.save('assets/prana_header_logo.png')
final_logo.save('logo.png')

print(f"Master logo successfully saved! Dimensions: {final_logo.size[0]} x {final_logo.size[1]} px.")

# Calculate vertical centers
arr = np.array(final_logo)
alpha = arr[:, :, 3]
emblem_y = np.where(alpha[:, :240] > 20)[0]
text_y = np.where(alpha[:, 250:] > 20)[0]
print(f"Emblem vertical span: {emblem_y.min()} to {emblem_y.max()} (center: {(emblem_y.min() + emblem_y.max()) / 2})")
print(f"Text vertical span: {text_y.min()} to {text_y.max()} (center: {(text_y.min() + text_y.max()) / 2})")
