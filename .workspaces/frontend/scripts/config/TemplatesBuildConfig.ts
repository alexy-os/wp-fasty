// config/TemplatesBuildConfig.ts
export interface TemplatesBuildTypes {
  uikitDir: string;     // './src/uikits'
  templatesDir: string;    // './src/templates'
  engine: 'latte' | 'hbs' | 'twig';
  dotToArr: boolean;
}

export const TemplatesBuildConfig: TemplatesBuildTypes = {
  uikitDir: './src/templates',
  templatesDir: './src/templates',
  engine: 'latte',
  dotToArr: true
};