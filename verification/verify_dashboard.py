from playwright.sync_api import sync_playwright
import time

def verify_dashboard_theme():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 720})
        page = context.new_page()

        # Navigate to Dashboard
        print("Navigating to dashboard...")
        page.goto("http://localhost:8080/dashboard")

        # Wait for page to load (check for specific elements like header)
        try:
            page.wait_for_selector("header", timeout=10000)
        except Exception as e:
            print("Failed to load dashboard:", e)
            page.screenshot(path="verification/dashboard_failed.png")
            browser.close()
            return

        # 1. Test Default Theme (Black Header)
        print("Setting theme to default...")
        page.evaluate("localStorage.setItem('user-theme', 'default')")
        page.reload()
        page.wait_for_selector("header")
        time.sleep(1) # Wait for Alpine/JS init

        # Check header background color via JS or just screenshot
        header_bg = page.evaluate("getComputedStyle(document.querySelector('header')).backgroundColor")
        print(f"Default Header BG: {header_bg}")
        # Expected: rgb(0, 0, 0) or close to it

        # Take screenshot
        page.screenshot(path="verification/dashboard_default.png")
        print("Captured dashboard_default.png")

        # 2. Test Blue Theme
        print("Setting theme to blue...")
        # We can simulate user interaction if there is a theme switcher,
        # but since I implemented the store, I can try to set it via JS console to test reactivity
        # Or just localStorage + reload

        # Let's try reactivity first: Access Alpine store
        # Alpine might not be on window.Alpine directly if module, but let's see.
        # Actually, simpler to just set localStorage and reload to prove persistent configuration.
        page.evaluate("localStorage.setItem('user-theme', 'blue')")
        page.reload()
        page.wait_for_selector("header")
        time.sleep(1)

        header_bg_blue = page.evaluate("getComputedStyle(document.querySelector('header')).backgroundColor")
        print(f"Blue Header BG: {header_bg_blue}")

        # Take screenshot
        page.screenshot(path="verification/dashboard_blue.png")
        print("Captured dashboard_blue.png")

        browser.close()

if __name__ == "__main__":
    verify_dashboard_theme()
