import os, sys, re, shutil, subprocess

BASE_DIR = r"c:\Users\syrko\Downloads\prana"
CASE_HTML = os.path.join(BASE_DIR, "case.html")
SCRATCH_DIR = r"C:\Users\syrko\.gemini\antigravity-ide\brain\637a33c4-a5bd-4e7d-a124-12fd65ee08df\scratch"
BABEL_JS = os.path.join(SCRATCH_DIR, "babel.min.js")
DO_COMPILE_JS = os.path.join(SCRATCH_DIR, "do_compile_v2.js")
APP_JSX = os.path.join(SCRATCH_DIR, "app_bundle_v2.jsx")
WEB_BUNDLE_JS = os.path.join(BASE_DIR, "prana-web-bundle", "app.bundle.js")
ANDROID_BUNDLE_JS = os.path.join(BASE_DIR, "android", "app", "src", "main", "assets", "public", "app.bundle.js")

def sync(build_apk=False):
    print(">>> 1. Reading JSX from case.html...")
    with open(CASE_HTML, "r", encoding="utf-8") as f:
        html = f.read()

    tag_open = '<script type="text/babel">'
    tag_close = '</script>'
    start = html.find(tag_open)
    end = html.rfind(tag_close)

    if start == -1 or end == -1:
        print("ERROR: Could not find <script type='text/babel'> in case.html!")
        return False

    jsx_code = html[start + len(tag_open):end].strip()
    with open(APP_JSX, "w", encoding="utf-8") as f:
        f.write(jsx_code)
    print(f"Extracted JSX ({len(jsx_code)} chars) -> {APP_JSX}")

    print(">>> 2. Compiling JSX into app.bundle.js with Babel...")
    res = subprocess.run(["node", DO_COMPILE_JS], capture_output=True, text=True)
    if res.returncode != 0:
        print("Babel compilation error:\n", res.stderr)
        return False
    print("Babel compilation successful!")

    print(">>> 3. Syncing app.bundle.js to Android assets...")
    shutil.copy2(WEB_BUNDLE_JS, ANDROID_BUNDLE_JS)
    print(f"Copied bundle -> {ANDROID_BUNDLE_JS} ({os.path.getsize(ANDROID_BUNDLE_JS)} bytes)")

    if build_apk:
        print(">>> 4. Building Android APK...")
        cmd = 'cmd /c "set JAVA_HOME=C:\\Program Files\\Microsoft\\jdk-21.0.12.101-hotspot&& set ANDROID_HOME=C:\\Users\\syrko\\AppData\\Local\\Android\\Sdk&& cd /d c:\\Users\\syrko\\Downloads\\prana\\android&& gradlew.bat assembleDebug"'
        build_res = subprocess.run(cmd, shell=True)
        if build_res.returncode == 0:
            apk_src = os.path.join(BASE_DIR, "android", "app", "build", "outputs", "apk", "debug", "app-debug.apk")
            apk_dst = os.path.join(BASE_DIR, "PranaHomeoCare_Case_Portal.apk")
            shutil.copy2(apk_src, apk_dst)
            print(f"SUCCESS: New APK generated at {apk_dst}")
        else:
            print("ERROR: APK build failed!")
            return False

    print(">>> All sync operations completed successfully! <<<")
    return True

if __name__ == "__main__":
    should_build_apk = "--apk" in sys.argv
    sync(build_apk=should_build_apk)
