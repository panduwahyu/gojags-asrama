"""Grade the eval outputs and build a viewer-friendly copy of an iteration.

Usage: python build_review.py iteration-1

Reads   <iteration>/eval-*/<config>/outputs/** (files mirror repo paths)
Writes  <iteration>-review/eval-*/<config>/run-1/{outputs/*.md, grading.json, timing.json, eval_metadata.json}
The eval viewer only shows top-level text files, so each run's files are concatenated into one Markdown file.
"""
import json
import re
import shutil
import sys
from pathlib import Path

CODE_EXTENSIONS = {".php", ".vue", ".ts", ".js", ".mjs", ".css"}
MAX_LINE = 120


def code_files(outputs: Path) -> list[Path]:
    return sorted(p for p in outputs.rglob("*") if p.is_file() and p.suffix in CODE_EXTENSIONS)


def rel(outputs: Path, path: Path) -> str:
    return path.relative_to(outputs).as_posix()


def any_path(outputs: Path, pattern: str) -> list[str]:
    regex = re.compile(pattern)
    return [rel(outputs, p) for p in code_files(outputs) if regex.search(rel(outputs, p))]


def grep(outputs: Path, pattern: str, suffixes: set[str] | None = None) -> list[str]:
    regex = re.compile(pattern)
    hits = []
    for path in code_files(outputs):
        if suffixes and path.suffix not in suffixes:
            continue
        if regex.search(path.read_text(encoding="utf-8", errors="replace")):
            hits.append(rel(outputs, path))
    return hits


def long_lines(outputs: Path) -> list[str]:
    hits = []
    for path in code_files(outputs):
        lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
        count = sum(1 for line in lines if len(line) > MAX_LINE)
        if count:
            hits.append(f"{rel(outputs, path)} ({count})")
    return hits


def exists(text: str, outputs: Path, pattern: str) -> dict:
    found = any_path(outputs, pattern)
    return {"text": text, "passed": bool(found), "evidence": ", ".join(found[:4]) or f"tidak ada berkas cocok /{pattern}/"}


def absent(text: str, outputs: Path, pattern: str, suffixes: set[str] | None = None) -> dict:
    hits = grep(outputs, pattern, suffixes)
    return {"text": text, "passed": not hits, "evidence": "bersih" if not hits else "ditemukan di: " + ", ".join(hits[:5])}


def contains(text: str, outputs: Path, pattern: str) -> dict:
    hits = grep(outputs, pattern)
    return {"text": text, "passed": bool(hits), "evidence": ", ".join(hits[:4]) or "tidak ditemukan"}


def no_long_lines(outputs: Path) -> dict:
    hits = long_lines(outputs)
    return {
        "text": f"Tidak ada baris kode > {MAX_LINE} karakter",
        "passed": not hits,
        "evidence": "bersih" if not hits else "berkas (jumlah baris panjang): " + ", ".join(hits[:6]),
    }


PHP = {".php"}
VUE = {".vue"}


def grade_backend(outputs: Path) -> list[dict]:
    return [
        exists("Status izin didefinisikan sebagai enum (app/Enums)", outputs, r"app/Enums/.+\.php$"),
        exists("Ada model Eloquent untuk izin keluar (app/Models)", outputs, r"app/Models/(?!User).+\.php$"),
        exists("Validasi di FormRequest (app/Http/Requests)", outputs, r"app/Http/Requests/.+\.php$"),
        exists("Bentuk JSON di API Resource (app/Http/Resources)", outputs, r"app/Http/Resources/.+\.php$"),
        exists("Rute ditulis di routes/api.php", outputs, r"routes/api\.php$"),
        exists("Ada feature test", outputs, r"tests/Feature/.+Test\.php$"),
        absent("Tanpa DB::table() di controller/model", outputs, r"DB::table\(", PHP),
        absent("Tanpa cabang expectsJson()/view() (Blade dihapus)", outputs, r"expectsJson\(|\bview\(", PHP),
        absent("Identifier Inggris (tanpa kolom waktu_keluar/alasan)", outputs, r"waktu_keluar|waktu_kembali|'alasan'", PHP),
        contains("Pesan 'Hanya izin berstatus Diajukan yang dapat diedit.' dipertahankan", outputs,
                 r"Hanya izin berstatus Diajukan yang dapat diedit\."),
        contains("Baris dikunci saat edit/tinjau (lockForUpdate)", outputs, r"lockForUpdate"),
        no_long_lines(outputs),
    ]


def grade_component(outputs: Path) -> list[dict]:
    vue_files = any_path(outputs, r"\.vue$")
    return [
        {"text": "Komponen dipecah menjadi >= 3 berkas .vue", "passed": len(vue_files) >= 3,
         "evidence": f"{len(vue_files)} berkas: " + ", ".join(vue_files[:6])},
        exists("Tipe dipindah ke types/", outputs, r"/types/.+\.ts$"),
        exists("Format tanggal WIB di utils/", outputs, r"/utils/.+\.ts$"),
        exists("Logika bersama di composable", outputs, r"/composables/.+\.ts$"),
        absent("Tanpa setInterval/setTimeout langsung di komponen .vue", outputs, r"setInterval|setTimeout", VUE),
        absent("Tanpa ternary bersarang untuk kelas status di template", outputs, r"\?[^?:\n]+:[^?:\n]+\?[^?:\n]+:", VUE),
        contains("Pesan 'Pengajuan sudah diproses admin dan tidak dapat diedit.' dipertahankan", outputs,
                 r"Pengajuan sudah diproses admin dan tidak dapat diedit\."),
        no_long_lines(outputs),
    ]


def grade_announcement(outputs: Path) -> list[dict]:
    return [
        exists("Model Eloquent pengumuman (app/Models)", outputs, r"app/Models/.+\.php$"),
        exists("Migrasi create tabel pengumuman", outputs, r"database/migrations/.+\.php$"),
        exists("Validasi di FormRequest", outputs, r"app/Http/Requests/.+\.php$"),
        exists("API Resource", outputs, r"app/Http/Resources/.+\.php$"),
        exists("Controller khusus fitur", outputs, r"app/Http/Controllers/.*Announcement.*\.php$"),
        exists("Rute di routes/api.php", outputs, r"routes/api\.php$"),
        exists("Feature test", outputs, r"tests/Feature/.+Test\.php$"),
        exists("Komponen di folder fitur (components/announcement/)", outputs, r"components/announcement/.+\.vue$"),
        exists("Tipe di types/", outputs, r"/types/.+\.ts$"),
        absent("Tanpa DB::table()", outputs, r"DB::table\(", PHP),
        absent("Tanpa cabang expectsJson()/view()", outputs, r"expectsJson\(|\bview\(", PHP),
        absent("Identifier Inggris (tanpa kolom judul/isi)", outputs, r"'judul'|'isi'|\bjudul:|\bisi:"),
        no_long_lines(outputs),
    ]


GRADERS = {0: grade_backend, 1: grade_component, 2: grade_announcement}


def flatten(outputs: Path, target: Path) -> None:
    """Write SUMMARY and one Markdown file holding every produced file."""
    target.mkdir(parents=True, exist_ok=True)
    summary = outputs / "SUMMARY.md"
    if summary.exists():
        shutil.copy(summary, target / "1-RINGKASAN.md")

    files = code_files(outputs)
    parts = ["# Berkas yang dihasilkan\n", "```", *[rel(outputs, p) for p in files], "```\n"]
    for path in files:
        body = path.read_text(encoding="utf-8", errors="replace")
        parts += [f"## {rel(outputs, path)}\n", f"```{path.suffix.lstrip('.')}", body.rstrip(), "```\n"]
    (target / "2-KODE.md").write_text("\n".join(parts), encoding="utf-8")


def main() -> None:
    iteration = Path(sys.argv[1])
    review = iteration.with_name(iteration.name + "-review")
    if review.exists():
        shutil.rmtree(review)

    for eval_dir in sorted(iteration.glob("eval-*")):
        metadata = json.loads((eval_dir / "eval_metadata.json").read_text(encoding="utf-8"))
        for config in ("with_skill", "without_skill"):
            outputs = eval_dir / config / "outputs"
            run_dir = review / eval_dir.name / config / "run-1"
            flatten(outputs, run_dir / "outputs")

            expectations = GRADERS[metadata["eval_id"]](outputs)
            passed = sum(1 for item in expectations if item["passed"])
            grading = {
                "expectations": expectations,
                "summary": {
                    "passed": passed,
                    "failed": len(expectations) - passed,
                    "total": len(expectations),
                    "pass_rate": round(passed / len(expectations), 2),
                },
            }
            (run_dir / "grading.json").write_text(json.dumps(grading, ensure_ascii=False, indent=2), encoding="utf-8")

            metadata["assertions"] = [item["text"] for item in expectations]
            (run_dir / "eval_metadata.json").write_text(
                json.dumps(metadata, ensure_ascii=False, indent=2), encoding="utf-8")

            timing = eval_dir / config / "timing.json"
            if timing.exists():
                shutil.copy(timing, run_dir / "timing.json")

            print(f"{eval_dir.name:45} {config:14} {passed}/{len(expectations)}")
            for item in expectations:
                if not item["passed"]:
                    print(f"    GAGAL: {item['text']} -- {item['evidence']}")


if __name__ == "__main__":
    main()
