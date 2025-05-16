// config/VariantsParserConfig.ts
export interface VariantsParserConfig {
  inputDir: string;           // './src/uikits/ui8px/core/tailwind/cva/ui'
  outputDir: string;          // './/src/assets/css'
  interfacesGlob: string;     // '**/*.tsx'
  variantsObject: string;     // 'cva' or 'tv'
  variantsKey: string;        // 'variants'
  defaultVariantsKey: string; // 'defaultVariants'
  outputIndex: string;        // 'index.css'
}

export const UI8KitConfig: VariantsParserConfig = {
  inputDir: './src/uikits/n4shadcn/src/ui',
  outputDir: './src/assets/css/ui8px/ui',
  interfacesGlob: '**/*.tsx',
  variantsObject: 'cva',
  variantsKey: 'variants',
  defaultVariantsKey: 'defaultVariants',
  outputIndex: 'variants.css',
};