// config/VariantsParserConfig.ts
export interface VariantsParserConfig {
  inputDir: string;           // './src/components/ui'
  outputDir: string;          // './src/components/css'
  interfacesGlob: string;     // '**/interface.ts'
  variantsObject: string;     // 'cva' or 'tv'
  variantsKey: string;        // 'variants'
  defaultVariantsKey: string; // 'defaultVariants'
}

export const UI8KitConfig: VariantsParserConfig = {
  inputDir: './src/uikits/ui8px/core/tailwind/cva/ui',
  outputDir: './src/assets/css/ui8px/ui',
  interfacesGlob: '**/*.tsx',
  variantsObject: 'cva',
  variantsKey: 'variants',
  defaultVariantsKey: 'defaultVariants',
};