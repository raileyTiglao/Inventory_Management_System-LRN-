/** @type {import('tailwindcss').Config} */
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
          DEFAULT: "#14171C",   // graphite black - page background
          raised: "#1C2028",    // surface / card background
          deep: "#0F1216",      // sidebar / deepest layer
        },
        surface: "#1C2028",
        card: "#242933",
        border: {
          DEFAULT: "#2A2F3A",
          light: "#343B47",
        },
        ink: {
          DEFAULT: "#E8E6DF",   // warm off-white text
          muted: "#8B92A0",     // secondary text
          dim: "#5C6270",       // tertiary / placeholder text
        },
        tag: {
          amber: "#E8A33D",    // primary accent - hazard amber (bin tag)
          amberdark: "#B87A22",
        },
        stock: {
          in: "#4F9D69",        // stock-in / success
          out: "#D9534F",       // stock-out / danger
          low: "#D9A72E",       // low-stock warning
        },
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
