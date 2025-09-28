#!/bin/bash
INPUT="plugin-structure.md"
CURRENT_FILE=""

while IFS= read -r line; do
  clean=$(echo "$line" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')

  if echo "$clean" | grep -q '^=== .* ===$'; then
    FILE=$(echo "$clean" | sed 's/^=== //; s/ ===$//')
    mkdir -p "$(dirname "$FILE")"
    CURRENT_FILE="$FILE"
    : > "$CURRENT_FILE"
    echo "Création : $FILE"
    continue
  fi

  if echo "$clean" | grep -q '^```'; then
    continue
  fi

  if [ -n "$CURRENT_FILE" ]; then
    echo "$line" >> "$CURRENT_FILE"
  fi
done < "$INPUT"

