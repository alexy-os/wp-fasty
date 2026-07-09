export function isDevEnv(): boolean {
  try {
    const env = (import.meta as unknown as { env?: { DEV?: boolean } }).env;
    if (env && typeof env.DEV === "boolean") return env.DEV;
  } catch {
    // import.meta may be unavailable in some contexts
  }
  return false;
}
