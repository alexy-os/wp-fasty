/**
 * Brick registry — every `ui/` primitive definition, in facade order.
 */

import type { BrickDef } from "../src/domain/model";

import alert from "./alert/alert.def";
import badge from "./badge/badge.def";
import block from "./block/block.def";
import box from "./box/box.def";
import breadcrumb from "./breadcrumb/breadcrumb.def";
import button from "./button/button.def";
import card from "./card/card.def";
import checkbox from "./checkbox/checkbox.def";
import container from "./container/container.def";
import dialog from "./dialog/dialog.def";
import disclosure from "./disclosure/disclosure.def";
import form from "./form/form.def";
import formControls from "./form/controls.def";
import grid from "./grid/grid.def";
import group from "./group/group.def";
import icon from "./icon/icon.def";
import iconbadge from "./iconbadge/iconbadge.def";
import image from "./image/image.def";
import inline from "./inline/inline.def";
import input from "./input/input.def";
import label from "./label/label.def";
import linebreak from "./linebreak/linebreak.def";
import link from "./link/link.def";
import list from "./list/list.def";
import radio from "./radio/radio.def";
import select from "./select/select.def";
import separator from "./separator/separator.def";
import stack from "./stack/stack.def";
import switchBrick from "./switch/switch.def";
import table from "./table/table.def";
import text from "./text/text.def";
import textarea from "./textarea/textarea.def";
import title from "./title/title.def";

export const bricks: BrickDef[] = [
  alert,
  badge,
  block,
  box,
  breadcrumb,
  button,
  card,
  checkbox,
  container,
  dialog,
  disclosure,
  form,
  formControls,
  grid,
  group,
  icon,
  iconbadge,
  image,
  inline,
  input,
  label,
  linebreak,
  link,
  list,
  radio,
  select,
  separator,
  stack,
  switchBrick,
  table,
  text,
  textarea,
  title,
];
