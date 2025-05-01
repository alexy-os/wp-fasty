// config.ts
import type { VariantsParserConfig } from '../types';

export const defaultConfig: VariantsParserConfig = {
  inputDir: './src/uikits/ui8px/core',
  outputDir: './src/uikits/ui8px/css',
  interfacesGlob: '**/interface.ts',
  variantsObject: 'cva',
  variantsKey: 'variants',
  defaultVariantsKey: 'defaultVariants',
};