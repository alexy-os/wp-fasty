// config.ts
import type { VariantsParserConfig } from '../types';

export const defaultConfig: VariantsParserConfig = {
  inputDir: './src/uikits/infobiz/core',
  outputDir: './src/uikits/infobiz/css',
  interfacesGlob: '**/interface.ts',
  variantsObject: 'cva',
  variantsKey: 'variants',
  defaultVariantsKey: 'defaultVariants',
};