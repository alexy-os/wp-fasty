export interface ValidationRule {
  name: string;
  description: string;
  validate: (path: any) => boolean;
  errorMessage: (path: any) => string;
}

export const reactValidationRules: ValidationRule[] = [
  {
    name: 'map-param-type',
    description: 'Map callback parameter should have type annotation :any',
    validate: (path) => {
      // Логика проверки
      return false; // Если не валидно
    },
    errorMessage: (path) => `The map parameter must have the type ": any"`
  },
  // Другие правила
];
