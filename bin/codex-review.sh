#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

REPO=""
BASE_BRANCH="main"
REVIEW_BODY="@codex review"
BRANCH_PREFIX="codex-review"
COMMIT_MESSAGE="WIP: Codex review"
PUSH_BRANCH="yes"
CREATE_PR="yes"
OPEN_PR="no"

usage() {
  cat <<'USAGE'
Usage:
  ./bin/codex-review.sh [options]

One-command GitHub Codex PR review flow:
  1. Detect repo
  2. If currently on main/master, create a review branch
  3. Stage current changes
  4. Commit changes if needed
  5. Push branch
  6. Create or find PR
  7. Comment: @codex review

Options:
  --repo OWNER/REPO       GitHub repository
  --base BRANCH           Base branch, default: main
  --body TEXT             Review comment body, default: @codex review
  --branch NAME           Branch name to create/use
  --message TEXT          Commit message
  --open                  Open PR in browser after creating/finding it
  --help                  Show help

Examples:
  ./bin/codex-review.sh
  ./bin/codex-review.sh --base main
  ./bin/codex-review.sh --branch codex-review-hero
  ./bin/codex-review.sh --body "@codex review for WordPress security issues"
USAGE
}

die() {
  echo "Error: $*" >&2
  exit 1
}

info() {
  echo "==> $*"
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --repo)
      REPO="${2:-}"
      [[ -n "$REPO" ]] || die "--repo requires OWNER/REPO"
      shift 2
      ;;
    --base)
      BASE_BRANCH="${2:-}"
      [[ -n "$BASE_BRANCH" ]] || die "--base requires branch name"
      shift 2
      ;;
    --body)
      REVIEW_BODY="${2:-}"
      [[ -n "$REVIEW_BODY" ]] || die "--body requires text"
      shift 2
      ;;
    --branch)
      TARGET_BRANCH="${2:-}"
      [[ -n "$TARGET_BRANCH" ]] || die "--branch requires branch name"
      shift 2
      ;;
    --message)
      COMMIT_MESSAGE="${2:-}"
      [[ -n "$COMMIT_MESSAGE" ]] || die "--message requires text"
      shift 2
      ;;
    --open)
      OPEN_PR="yes"
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      die "Unknown option: $1"
      ;;
  esac
done

command -v git >/dev/null 2>&1 || die "git is required"
command -v gh >/dev/null 2>&1 || die "GitHub CLI is required. Install with: brew install gh"

git -C "$ROOT_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "$ROOT_DIR is not a git repository"
gh auth status >/dev/null 2>&1 || die "GitHub CLI is not logged in. Run: gh auth login"

cd "$ROOT_DIR"

if [[ -z "$REPO" ]]; then
  REPO="$(gh repo view --json nameWithOwner -q '.nameWithOwner' 2>/dev/null || true)"
fi

if [[ -z "$REPO" ]]; then
  origin_url="$(git remote get-url origin 2>/dev/null || true)"
  if [[ "$origin_url" =~ github.com[:/]([^/]+/[^/.]+)(\.git)?$ ]]; then
    REPO="${BASH_REMATCH[1]}"
  fi
fi

[[ -n "$REPO" ]] || die "Could not detect GitHub repo. Pass --repo OWNER/REPO."

current_branch="$(git branch --show-current)"
[[ -n "$current_branch" ]] || die "You are not on a named branch"

if [[ -z "${TARGET_BRANCH:-}" ]]; then
  timestamp="$(date +%Y%m%d-%H%M%S)"
  TARGET_BRANCH="$BRANCH_PREFIX-$timestamp"
fi

if [[ "$current_branch" == "$BASE_BRANCH" || "$current_branch" == "main" || "$current_branch" == "master" ]]; then
  info "Creating review branch: $TARGET_BRANCH"
  git checkout -b "$TARGET_BRANCH"
  current_branch="$TARGET_BRANCH"
else
  info "Using current branch: $current_branch"
fi

if git diff --quiet && git diff --cached --quiet; then
  info "No uncommitted changes found"
else
  info "Staging current changes"
  git add -A

  if git diff --cached --quiet; then
    info "Nothing staged after git add"
  else
    info "Committing current changes"
    git commit -m "$COMMIT_MESSAGE"
  fi
fi

if ! git rev-parse --verify "$BASE_BRANCH" >/dev/null 2>&1; then
  if git ls-remote --exit-code --heads origin "$BASE_BRANCH" >/dev/null 2>&1; then
    git fetch origin "$BASE_BRANCH:$BASE_BRANCH"
  else
    die "Base branch '$BASE_BRANCH' does not exist locally or on origin"
  fi
fi

if [[ "$current_branch" == "$BASE_BRANCH" ]]; then
  die "Current branch and base branch are both '$BASE_BRANCH'. Cannot create PR."
fi

info "Pushing branch: $current_branch"
git push -u origin "$current_branch"

PR_NUMBER="$(gh pr list --repo "$REPO" --head "$current_branch" --state open --json number -q '.[0].number' 2>/dev/null || true)"

if [[ -z "$PR_NUMBER" ]]; then
  info "Creating PR from $current_branch to $BASE_BRANCH"
  gh pr create \
    --repo "$REPO" \
    --base "$BASE_BRANCH" \
    --head "$current_branch" \
    --title "$COMMIT_MESSAGE" \
    --body "Temporary PR for Codex code review."

  PR_NUMBER="$(gh pr list --repo "$REPO" --head "$current_branch" --state open --json number -q '.[0].number' 2>/dev/null || true)"
fi

[[ -n "$PR_NUMBER" ]] || die "Could not find or create PR"

info "Requesting Codex review on $REPO PR #$PR_NUMBER"
gh pr comment "$PR_NUMBER" --repo "$REPO" --body "$REVIEW_BODY"

info "Posted: $REVIEW_BODY"
info "PR URL:"
gh pr view "$PR_NUMBER" --repo "$REPO" --json url -q '.url'

if [[ "$OPEN_PR" == "yes" ]]; then
  gh pr view "$PR_NUMBER" --repo "$REPO" --web
fi
