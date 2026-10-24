/** field type → component (Puck's field types; editor/src/fields/schema.ts builds the definitions) */
import type { Component } from "svelte";
import type { FieldType } from "./schema";
import TextField from "./TextField.svelte";
import NumberField from "./NumberField.svelte";
import SelectField from "./SelectField.svelte";
import RadioField from "./RadioField.svelte";
import BooleanField from "./BooleanField.svelte";
import ObjectField from "./ObjectField.svelte";
import ArrayField from "./ArrayField.svelte";
import MapField from "./MapField.svelte";
import JsonField from "./JsonField.svelte";

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const registry: Record<Exclude<FieldType, "binding">, Component<any>> & Partial<Record<FieldType, Component<any>>> = {
  text: TextField,
  textarea: TextField,
  number: NumberField,
  select: SelectField,
  radio: RadioField,
  boolean: BooleanField,
  object: ObjectField,
  array: ArrayField,
  map: MapField,
  json: JsonField,
};
