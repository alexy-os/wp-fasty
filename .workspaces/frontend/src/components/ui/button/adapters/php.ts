import { buttonVariants } from '../interface';
import type { ButtonProps } from '../interface';

export function generatePHPTemplate(props: ButtonProps): string {
  const classes = buttonVariants({ variant: props.variant, size: props.size });

  return `<button class="${classes}" <?= $disabled ? 'disabled' : '' ?>>
    <?= $label ?>
  </button>`;
}
