import os
import subprocess
import re
from datetime import datetime

# --- CONFIGURATION ---
CHANGELOG_FILE = "CHANGELOG.md"

# --- 1. THE UNIVERSAL EXTENSION LIBRARY ---
EXT_MAP = {
    # 🌍 Web & Frontend
    '.html': '🖼️ View', '.css': '🎨 Styles', '.scss': '🎨 Styles', '.less': '🎨 Styles',
    '.js': '⚡ Logic (JS)', '.ts': '⚡ Logic (TS)', '.jsx': '⚛️ React UI', '.tsx': '⚛️ React UI',
    '.vue': '🟢 Vue Component', '.svelte': '🟠 Svelte Component', '.php': '🐘 Backend (PHP)',
    
    # ⚙️ Systems & Backend
    '.py': '🐍 Python', '.java': '☕ Java', '.kt': '🤖 Kotlin', '.rs': '🦀 Rust',
    '.go': '🐹 Go', '.rb': '💎 Ruby', '.c': '⚙️ C', '.cpp': '⚙️ C++', '.h': '⚙️ Header',
    '.cs': '#️⃣ C#', '.swift': '🦅 Swift', '.dart': '🎯 Dart', '.lua': '🌙 Lua',
    '.pl': '🐫 Perl', '.sh': '🐚 Shell Script', '.bat': '🐚 Batch Script',

    # 🐳 DevOps & Config
    '.dockerignore': '🐳 Docker', '.yml': '🔧 CI/CD', '.yaml': '🔧 CI/CD',
    '.xml': '🔧 Config', '.json': '🔧 Config', '.env': '🔐 Secrets',
    '.gitignore': '⚙️ Git Config', '.sql': '🗄️ Database', '.md': '📚 Docs',
    
    # 🖼️ Assets
    '.png': '🖼️ Image', '.jpg': '🖼️ Image', '.jpeg': '🖼️ Image', 
    '.svg': '🖼️ Vector', '.ico': '🖼️ Icon', '.ttf': '🔤 Font',
}

# --- 2. SPECIAL FILENAME OVERRIDES ---
# Some files don't have extensions or need specific labels
SPECIAL_FILES = {
    'Dockerfile': '🐳 Docker Config',
    'Makefile': '🛠️ Build Config',
    'Jenkinsfile': '🤵 Jenkins CI',
    'README.md': '📖 Main Documentation',
    'LICENSE': '⚖️ Legal',
    'go.mod': '📦 Go Modules',
    'package.json': '📦 Node Packages',
    'requirements.txt': '📦 Python Deps',
    'pom.xml': '📦 Maven Deps',
    'build.gradle': '📦 Gradle Config',
    'Cargo.toml': '📦 Rust Crates',
}

# --- 3. SMART FOLDER DETECTION ---
FOLDER_MAP = {
    'admin': '🛡️ Admin Panel', 'api': '🔌 API',
    'assets': '🖼️ Assets', 'static': '🖼️ Static', 'public': '🌍 Public',
    'bin': '📦 Binaries', 'build': '📦 Build', 'dist': '📦 Distribution',
    'config': '⚙️ Config', 'conf': '⚙️ Config',
    'controllers': '🎮 Controllers', 'models': '🧱 Models', 'views': '🖼️ Views',
    'css': '🎨 Styles', 'js': '⚡ Scripts',
    'db': '🗄️ Database', 'migrations': '🗄️ Migrations',
    'docs': '📚 Docs', 'doc': '📚 Docs',
    'include': '🔌 Includes', 'includes': '🔌 Includes', 'lib': '📚 Libs',
    'src': '🛠️ Source', 'test': '🧪 Tests', 'tests': '🧪 Tests',
    'utils': '🛠️ Utilities', 'helpers': '🛠️ Helpers',
    '.github': '🤖 GitHub Actions', '.vscode': '💻 IDE Config',
}

# --- 4. OMNISCIENT PATTERN MATCHING ---
# Regex to detect code structures across languages
PATTERNS = [
    # Function Definitions (PHP, JS, Python, Rust, Go, Swift, Kotlin)
    (r'^\+\s*(function|def|fn|fun|func)\s+([a-zA-Z0-9_]+)', "✨ Added function `{}`"),
    (r'^\-\s*(function|def|fn|fun|func)\s+([a-zA-Z0-9_]+)', "🔥 Removed function `{}`"),
    
    # Classes & Structs
    (r'^\+\s*(class|struct|interface|trait|impl)\s+([a-zA-Z0-9_]+)', "📦 Created `{}`"),
    
    # Database (SQL)
    (r'^\+\s*CREATE TABLE\s+`?([a-zA-Z0-9_]+)`?', "🗄️ Created Table `{}`"),
    (r'^\+\s*ALTER TABLE\s+`?([a-zA-Z0-9_]+)`?', "⚠️ Database Change in `{}`"),
    
    # Variables / Properties (Generic)
    (r'^\+\s*(public|private|protected|const|let|var)\s+[\$]?([a-zA-Z0-9_]+)', "🔹 Added var `{}`"),
    
    # Keywords
    (r'password|auth|secret|token', "🔒 Security Logic"),
    (r'TODO|FIXME|HACK', "🚧 Work in Progress"),
    (r'console\.log|print|System\.out', "🐛 Debugging"),
]

def run_command(command):
    return subprocess.run(command, shell=True, capture_output=True, text=True).stdout.strip()

def detect_context(filepath):
    filename = os.path.basename(filepath)
    ext = os.path.splitext(filename)[1].lower()
    parts = filepath.split('/')

    # 1. Check Exact Filename (Docker, Makefiles)
    if filename in SPECIAL_FILES: return SPECIAL_FILES[filename]

    # 2. Check Folder Names (Highest Priority)
    for folder in parts[:-1]:
        if folder.lower() in FOLDER_MAP:
            return FOLDER_MAP[folder.lower()]

    # 3. Check Extension (Fallback)
    return EXT_MAP.get(ext, '🔧 General')

def analyze_file_diff(filename):
    diff = run_command(f"git diff HEAD~1 HEAD -- {filename}")
    details = []
    
    # Regex Scanning
    for pattern, template in PATTERNS:
        matches = re.findall(pattern, diff, re.MULTILINE | re.IGNORECASE)
        for match in matches:
            # Handle tuple groups from regex
            item_name = match[-1] if isinstance(match, tuple) else match
            msg = template.format(item_name) if "{}" in template else template
            if msg not in details: details.append(msg)

    # Heuristic Fallback
    if not details:
        added = len(re.findall(r'^\+', diff, re.MULTILINE))
        removed = len(re.findall(r'^\-', diff, re.MULTILINE))
        if added > 15 and removed < 2: details.append("✨ Major Implementation")
        elif removed > 15 and added < 2: details.append("🔥 Major Cleanup")
        elif "fix" in filename.lower(): details.append("🐛 Bug Fix")
        elif "test" in filename.lower(): details.append("🧪 Test Update")
        else: details.append("⚡ Update")

    return list(set(details))[:2] # Top 2 insights only

def get_smart_changes():
    output = run_command("git diff-tree --no-commit-id --name-status -r HEAD")
    changes = []
    if not output: return changes

    for line in output.split("\n"):
        parts = line.split()
        if len(parts) < 2: continue
        
        status, filepath = parts[0], parts[1]
        filename = os.path.basename(filepath)
        
        if filename in [CHANGELOG_FILE, "command.py"]: continue

        context = detect_context(filepath)

        if status.startswith("A"):
            entry = f"- **{context}:** 🎉 Created `{filename}`"
        elif status.startswith("D"):
            entry = f"- **{context}:** 🗑️ Deleted `{filename}`"
        elif status.startswith("R"):
            entry = f"- **{context}:** 🚚 Renamed/Moved `{filename}`"
        else:
            insights = analyze_file_diff(filepath)
            desc = ", ".join(insights)
            entry = f"- **{context}:** {desc} in `{filename}`"
        
        changes.append(entry)
    
    return changes

def update_changelog(changes):
    if not changes: return False
    
    date_str = datetime.now().strftime("%Y-%m-%d")
    new_entry = f"\n## [Auto-Log] - {date_str}\n" + "\n".join(changes) + "\n"

    content = "# 🔄 Changelog\n\n"
    if os.path.exists(CHANGELOG_FILE):
        with open(CHANGELOG_FILE, "r") as f: content = f.read()

    # Smart Insert
    if "\n\n" in content:
        parts = content.split("\n\n", 1)
        final_content = parts[0] + "\n" + new_entry + "\n" + parts[1]
    else:
        final_content = content + new_entry

    with open(CHANGELOG_FILE, "w") as f: f.write(final_content)
    return True

if __name__ == "__main__":
    print("🧠 Omniscient Bot analyzing...")
    changes = get_smart_changes()
    if update_changelog(changes):
        print(f"✅ Updated {CHANGELOG_FILE}")
    else:
        print("💤 No changes.")
