import {mount, unmount} from "svelte";
import AgentPanel from "./AgentPanel.svelte";
import type {FeatureContext, FeatureFactory} from "../../../plugins/wpfasty-builder/editor/src/features";
import "./agent.css";
class Context {value = $state.raw<FeatureContext>(); constructor(value: FeatureContext) {this.value = value;}}
const factory: FeatureFactory = (target, initial) => {const state = new Context(initial); const panel = mount(AgentPanel, {target, props: {get context() {return state.value!;}}}); return {update(context) {state.value = context;}, destroy() {void unmount(panel);}};};
window.WPFastyBuilderFeatures ??= {};
window.WPFastyBuilderFeatures["builder-agent"] = factory;
