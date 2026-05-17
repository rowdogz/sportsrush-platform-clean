import { useTheme } from "../contexts/ThemeContext";

export function ThemeToggle() {
  const { theme, toggleTheme } = useTheme();
  const isDark = theme === "dark";

  return (
    <button
      aria-label={isDark ? "Switch to light mode" : "Switch to dark mode"}
      className="icon-button"
      type="button"
      onClick={toggleTheme}
    >
      <span className="theme-toggle-glyph" aria-hidden="true">
        {isDark ? "Sun" : "Moon"}
      </span>
    </button>
  );
}
