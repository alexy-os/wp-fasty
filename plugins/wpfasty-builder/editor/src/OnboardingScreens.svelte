<script lang="ts">
import Box from './ui/box/Box.svelte';
import Button from './ui/button/Button.svelte';
import Text from './ui/text/Text.svelte';
import Image from './ui/image/Image.svelte';
import type {TemplatePage} from './types';
import {screenCards,onboardingCopy as copy} from './onboarding';
import {previewSrc} from './theme';
import {human} from './util';
let {pages,dark=false,onselect}:{pages:TemplatePage[];dark?:boolean;onselect:(screen:TemplatePage)=>void}=$props();
const cards=$derived(screenCards(pages));
let peek=$state<string|null>(null);
</script>
<Box tag="section" class="wpfb-onboarding-screens" aria-label={copy.screens}>
{#each cards as page(page.id)}
<Box tag="article" class="wpfb-screen-card" onpointerleave={()=>peek=null}>
<Button class="wpfb-screen-select" aria-describedby={peek===page.id?`screen-peek-${page.id}`:undefined} onfocus={()=>peek=page.id} onblur={()=>peek=null} onpointerenter={()=>peek=page.id} onkeydown={(e:KeyboardEvent)=>{if(e.key==='Escape')peek=null;}} onclick={()=>{peek=null;onselect(page);}}>
{#if page.preview}<Image src={previewSrc(page.preview,dark)} alt={human(page.id)} />{/if}
<Text tag="span">{human(page.id)}</Text>
</Button>
{#if peek===page.id&&page.preview}<Box tag="div" id={`screen-peek-${page.id}`} class="wpfb-screen-peek" role="tooltip"><Image src={previewSrc(page.preview,dark)} alt={copy.preview} /></Box>{/if}
</Box>
{/each}
</Box>
