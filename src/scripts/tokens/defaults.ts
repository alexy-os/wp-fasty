export const defaultTokens = {
  typography: {
    "font-family": {
      sans: { value: "Inter, -apple-system, system-ui, sans-serif" }
    },
    "font-size": {
      xs: { value: "0.75rem" },     // 12px
      sm: { value: "0.875rem" },    // 14px
      base: { value: "1rem" },      // 16px
      lg: { value: "1.125rem" },    // 18px
      xl: { value: "1.25rem" },     // 20px
      "2xl": { value: "1.5rem" },   // 24px
      "3xl": { value: "1.875rem" }, // 30px
      "4xl": { value: "2.25rem" },  // 36px
      "5xl": { value: "3rem" },     // 48px
      "6xl": { value: "3.75rem" },  // 60px
    },
    "font-weight": {
      light: { value: "300" },
      normal: { value: "400" },
      medium: { value: "500" },
      semibold: { value: "600" },
      bold: { value: "700" },
      extrabold: { value: "800" }
    },
    "line-height": {
      none: { value: "1" },
      tight: { value: "1.25" },
      snug: { value: "1.375" },
      normal: { value: "1.5" },
      relaxed: { value: "1.625" },
      loose: { value: "2" }
    }
  },
  spacing: {
    0: { value: "0" },
    1: { value: "0.25rem" },   // 4px
    2: { value: "0.5rem" },    // 8px
    3: { value: "0.75rem" },   // 12px
    4: { value: "1rem" },      // 16px
    5: { value: "1.25rem" },   // 20px
    6: { value: "1.5rem" },    // 24px
    8: { value: "2rem" },      // 32px
    10: { value: "2.5rem" },   // 40px
    12: { value: "3rem" },     // 48px
    16: { value: "4rem" },     // 64px
    20: { value: "5rem" },     // 80px
    24: { value: "6rem" }      // 96px
  },
  shadow: {
    sm: { 
      value: "0 1px 2px 0 rgb(0 0 0 / 0.05)"
    },
    DEFAULT: { 
      value: "0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)"
    },
    md: { 
      value: "0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)"
    },
    lg: { 
      value: "0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)"
    },
    xl: { 
      value: "0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)"
    },
    "2xl": { 
      value: "0 25px 50px -12px rgb(0 0 0 / 0.25)"
    },
    inner: { 
      value: "inset 0 2px 4px 0 rgb(0 0 0 / 0.05)"
    }
  },
  // Add border-width for full compatibility
  "border-width": {
    DEFAULT: { value: "1px" },
    0: { value: "0px" },
    2: { value: "2px" },
    4: { value: "4px" },
    8: { value: "8px" }
  },
  // Add z-index
  "z-index": {
    0: { value: "0" },
    10: { value: "10" },
    20: { value: "20" },
    30: { value: "30" },
    40: { value: "40" },
    50: { value: "50" },
    auto: { value: "auto" }
  }
}; 