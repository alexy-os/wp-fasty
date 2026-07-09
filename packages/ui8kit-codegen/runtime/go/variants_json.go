package uiutils

import (
	"encoding/json"
	"fmt"
)

// VariantRecipe is the JSON shape for *.variants.json (shared with React CVA recipes).
type VariantRecipe struct {
	ID       string                       `json:"id"`
	Base     string                       `json:"base"`
	Keys     []string                     `json:"keys"`
	Defaults map[string]string            `json:"defaults"`
	ByKey    map[string]map[string]string `json:"byKey"`
	Meta     map[string]string            `json:"meta,omitempty"`
}

// ToVariants converts a recipe into the in-memory CVA map used by Compose.
func (r VariantRecipe) ToVariants() Variants {
	return Variants{
		Base:     r.Base,
		Keys:     r.Keys,
		ByKey:    r.ByKey,
		Defaults: r.Defaults,
	}
}

// ParseVariantRecipe unmarshals *.variants.json bytes.
func ParseVariantRecipe(data []byte) (VariantRecipe, error) {
	var recipe VariantRecipe
	if err := json.Unmarshal(data, &recipe); err != nil {
		return VariantRecipe{}, fmt.Errorf("variants JSON: %w", err)
	}
	if recipe.ByKey == nil {
		return VariantRecipe{}, fmt.Errorf("variants JSON: missing byKey")
	}
	return recipe, nil
}

// MustParseVariantRecipe parses recipe JSON or panics (for generated init blocks).
func MustParseVariantRecipe(data []byte) VariantRecipe {
	recipe, err := ParseVariantRecipe(data)
	if err != nil {
		panic(err)
	}
	return recipe
}
