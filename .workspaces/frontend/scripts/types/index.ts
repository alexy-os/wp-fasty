// types.ts
export interface VariantsParserConfig {
  inputDir: string;           // './src/components/ui'
  outputDir: string;          // './src/components/css'
  interfacesGlob: string;     // '**/interface.ts'
  variantsObject: string;     // 'cva' или 'tv'
  variantsKey: string;        // 'variants'
  defaultVariantsKey: string; // 'defaultVariants'
}