import { buttonVariants } from '../Button.types';
import type { ButtonProps } from '../Button.types';

export function generatePHPTemplate(props: ButtonProps): string {
  const classes = buttonVariants({ variant: props.variant, size: props.size });

  return `<button class="${classes}" <?= $disabled ? 'disabled' : '' ?>>
    <?= $label ?>
  </button>`;
}
