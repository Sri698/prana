import re

with open('paediatric-care.html', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Find inline styles with overflow hidden
matches = re.findall(r'<(?:section|div|main|body)[^>]*overflow[^>]*>', content, re.IGNORECASE)
for m in matches[:10]:
    print(m[:200])
    print()

# Check if nav-backdrop is active or any element is stuck open
if 'nav-backdrop' in content:
    print("nav-backdrop present in HTML:", content.count('nav-backdrop'), "times")

# Check for any body style that has overflow hidden hard-coded
body_styles = re.findall(r'<body[^>]*>', content, re.IGNORECASE)
print("Body tags:", body_styles)

# Look for any js inline that might block
script_matches = re.findall(r'<script[^>]*>.*?</script>', content, re.IGNORECASE | re.DOTALL)
for sm in script_matches:
    if 'overflow' in sm.lower() or 'scroll' in sm.lower():
        print("Script with overflow/scroll:", sm[:300])
