/** @type {import('tailwindcss').Config} */

// Colors are driven by CSS custom properties (space-separated RGB triplets,
// see styles/input.css :root / [data-theme="dark"]) so the app can switch
// palettes at runtime while keeping Tailwind's opacity-modifier syntax
// (e.g. bg-tag-amber/10) working.
function withOpacity(variableName) {
  return ({ opacityValue }) => {
    if (opacityValue !== undefined) {
      return `rgb(var(${variableName}) / ${opacityValue})`;
    }
    return `rgb(var(${variableName}))`;
  };
}

module.exports = {
  content: [
    "./pages/**/*.php",
    "./components/**/*.php",
    "./auth/**/*.php",
    "./*.php",
  ],
  theme: {
    extend: {
      colors: {
        base: {
          DEFAULT: withOpacity("--color-base"),   // page background
          raised: withOpacity("--color-base-raised"),
          deep: withOpacity("--color-base-deep"),  // sidebar / deepest layer
        },
        surface: withOpacity("--color-surface"),
        card: withOpacity("--color-card"),
        border: {
          DEFAULT: withOpacity("--color-border"),
          light: withOpacity("--color-border-light"),
        },
        ink: {
          DEFAULT: withOpacity("--color-ink"),        // primary text
          muted: withOpacity("--color-ink-muted"),    // secondary text
          dim: withOpacity("--color-ink-dim"),        // tertiary / placeholder text
          // Text drawn on top of the amber accent (buttons/badges) must stay
          // dark regardless of theme, since the amber accent itself doesn't
          // change between light/dark.
          onamber: "#14171C",
        },
        tag: {
          amber: withOpacity("--color-tag-amber"),    // primary accent - hazard amber (bin tag)
          amberdark: withOpacity("--color-tag-amberdark"),
        },
        stock: {
          in: withOpacity("--color-stock-in"),        // stock-in / success
          out: withOpacity("--color-stock-out"),      // stock-out / danger
          low: withOpacity("--color-stock-low"),      // low-stock warning
        },
        // Neutral hover/tint overlay — darkens on light surfaces, lightens on
        // dark surfaces (replaces hardcoded bg-white/5-style tints).
        overlay: withOpacity("--color-overlay"),
      },
      fontFamily: {
        display: ["'Space Grotesk'", "sans-serif"],
        body: ["'Inter'", "sans-serif"],
        mono: ["'JetBrains Mono'", "monospace"],
      },
      borderRadius: {
        tag: "4px",
      },
      backgroundImage: {
        'perf-h': "repeating-linear-gradient(90deg, transparent, transparent 6px, rgba(232,166,61,0.35) 6px, rgba(232,166,61,0.35) 8px)",
      },
      boxShadow: {
        tag: "0 1px 0 0 rgba(0,0,0,0.4)",
      },
    },
  },
  plugins: [],
}
