<script lang="ts">
import Box from './ui/box/Box.svelte';
import Button from './ui/button/Button.svelte';
import Text from './ui/text/Text.svelte';
import {shellChoices,onboardingCopy as copy,type PageShell} from './onboarding';
let {value,onchange}:{value:PageShell;onchange:(mode:PageShell)=>void}=$props();
</script>
<Box tag="section" class="wpfb-onboarding-choices" aria-label={copy.label}>
{#each shellChoices as choice(choice.id)}
<Button class="wpfb-shell-choice" aria-pressed={value===choice.id?'true':'false'} onclick={()=>onchange(choice.id)}>
<Box tag="div" class={`wpfb-shell-diagram shell-${choice.id}`} aria-hidden="true">
{#if choice.id!=='blank'}<Box tag="div" class="shell-header" />{/if}
<Box tag="div" class="shell-body">{#if choice.id==='sidebar'}<Box tag="div" class="shell-sidebar" />{/if}</Box>
{#if choice.id!=='blank'}<Box tag="div" class="shell-footer" />{/if}
</Box><Text tag="span">{choice.label}</Text>
</Button>
{/each}
</Box>
