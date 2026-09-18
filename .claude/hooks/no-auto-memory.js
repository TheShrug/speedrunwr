#!/usr/bin/env node
// PreToolUse hook (Read|Write|Edit): gate Claude Code's auto-memory store.
//
// Rationale: anything durable enough to be worth remembering *about this project* is durable enough
// to belong in a repo, where it's reviewable, diffable, and visible to every contributor — not in a
// per-user directory under ~/.claude that only one machine can see.
//
// The exception is memory about *the user* — how they like to work, how they prefer to interact,
// communication style. That is genuinely per-person rather than per-repo, it doesn't belong in a
// repo other contributors read, and it's the one thing the memory store is actually for.
//
// So this returns "ask" rather than "deny": every write to the memory tree stops and prompts, and
// the reason below is the test to apply before answering. `autoMemoryEnabled` is left ON so that
// path exists at all — this hook, not the setting, is what keeps it deliberate.

let raw = "";
process.stdin.on("data", (chunk) => (raw += chunk));
process.stdin.on("end", () => {
  let input;
  try {
    input = JSON.parse(raw);
  } catch {
    // Malformed payload: stay out of the way rather than blocking real work.
    process.exit(0);
  }

  const filePath = input?.tool_input?.file_path ?? "";
  const normalized = filePath.replace(/\\/g, "/");

  // The auto-memory store lives at ~/.claude/projects/<sanitized-cwd>/memory/. Match on that
  // shape rather than one hard-coded absolute path so it still fires if the project directory
  // is renamed or autoMemoryDirectory is repointed at another .claude tree.
  const isMemoryPath =
    /\/\.claude\/projects\/[^/]+\/memory(\/|$)/.test(normalized) ||
    /\/\.claude\/.*\/memory\/MEMORY\.md$/.test(normalized);

  if (!isMemoryPath) process.exit(0);

  const reason = [
    "Memory-store write. Decide which of these it is before answering — and ASK THE USER before",
    "writing one either way. A memory is never created silently.",
    "",
    "ALLOWED — it's about THE USER, not the code:",
    "  - how they like to work, and how they want work presented or paced",
    "  - how they prefer to interact; communication and feedback style",
    "  - standing preferences that would hold on any repo, not just this one",
    "  Genuinely per-person, so a repo other people read is the wrong home for it. Confirm the",
    "  wording with them, then write it.",
    "",
    "NOT ALLOWED — anything else. Work out which of these three it is:",
    "",
    "1. IT'S ABOUT THE APP ITSELF — how it's built, how to run it locally, a convention in the",
    "   Laravel source. Write it here:",
    "     - README.md    what this is, how to build and run it",
    "     - CLAUDE.md    conventions for working in this repo",
    "",
    "2. IT'S AN OPERATIONAL FACT — hosting, DNS, ingress, the Coolify resource, where it deploys.",
    "   That does not live in this repo. It lives in the homelab vault, in Projects/speedrunwr.md",
    "   (github.com/TheShrug/homelab). Put it there so it sits beside the server notes it",
    "   depends on, and link rather than restating it here.",
    "",
    "3. IT'S WORK TO BE DONE. That's a GitHub issue on this repo, not a note anywhere. Use the",
    "   Work item template; add the matching type: label.",
    "",
    "And consider that it may be a SYMPTOM: a fact you have to *remember* to work in a codebase",
    "is often a defect rather than a quirk — a footgun you route around, a convention honoured in",
    "some places and not others, code or processes going against common software development best",
    "practices. Recording it just makes the defect survivable instead of fixed.",
    "\"Remember that X is weird\" is nearly always a bug report wearing a disguise. Raise it.",
  ].join("\n");

  process.stdout.write(
    JSON.stringify({
      hookSpecificOutput: {
        hookEventName: "PreToolUse",
        permissionDecision: "ask",
        permissionDecisionReason: reason,
      },
    })
  );
  process.exit(0);
});
