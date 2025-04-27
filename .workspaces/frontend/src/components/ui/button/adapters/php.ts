import { ButtonProps } from '../interface';

/**
 * Generate PHP Button component template
 */
export function generatePHPButton(props: Partial<ButtonProps> = {}): string {
  const variant = props.variant || 'primary';
  const size = props.size || 'default';
  const disabled = props.disabled || false;
  const label = props.label || '';

  // Generate semantic class names
  const classes = `button button-${variant} ${size !== "default" ? `button-${size}` : ""}`;

  // Generate PHP template with proper escaping
  return `<button 
  class="<?= esc_attr('${classes}') ?>"
  <?= ${disabled} ? 'disabled' : '' ?>
>
  <?= esc_html('${label}') ?>
</button>`;
}

/**
 * Generate PHP code for a Button component
 */
export function generatePHPButtonClass(): string {
  return `<?php
/**
 * Button component
 */
class Button {
  private $variant = 'primary';
  private $size = 'default';
  private $disabled = false;
  private $label = '';
  
  /**
   * Set button variant
   * 
   * @param string $variant One of: primary, secondary, destructive, outline, ghost, link
   * @return self
   */
  public function variant(string $variant): self {
    $this->variant = $variant;
    return $this;
  }
  
  /**
   * Set button size
   * 
   * @param string $size One of: default, sm, lg, icon
   * @return self
   */
  public function size(string $size): self {
    $this->size = $size;
    return $this;
  }
  
  /**
   * Set button disabled state
   * 
   * @param bool $disabled
   * @return self
   */
  public function disabled(bool $disabled = true): self {
    $this->disabled = $disabled;
    return $this;
  }
  
  /**
   * Set button label
   * 
   * @param string $label
   * @return self
   */
  public function label(string $label): self {
    $this->label = $label;
    return $this;
  }
  
  /**
   * Render button
   * 
   * @return string
   */
  public function render(): string {
    $classes = 'button button-' . $this->variant;
    if ($this->size !== 'default') {
      $classes .= ' button-' . $this->size;
    }
    
    ob_start();
    ?>
    <button 
      class="<?= esc_attr($classes) ?>"
      <?= $this->disabled ? 'disabled' : '' ?>
    >
      <?= esc_html($this->label) ?>
    </button>
    <?php
    return ob_get_clean();
  }
}`;
}