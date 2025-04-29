// config.ts
import type { VariantsParserConfig } from '../types';

export const defaultConfig: VariantsParserConfig = {
  inputDir: './src/components/ui',
  outputDir: './src/components/css',
  interfacesGlob: '**/interface.ts',
  variantsObject: 'cva',
  variantsKey: 'variants',
  defaultVariantsKey: 'defaultVariants',
};