param(
    [ValidateSet('dev', 'build', 'typecheck', 'preview')]
    [string]$Task = 'dev'
)

$projectRoot = Split-Path -Parent $PSScriptRoot
$localNode = Join-Path $projectRoot '.tools/node_modules/node/bin/node.exe'
$localNpm = Join-Path $projectRoot '.tools/node_modules/npm/bin/npm-cli.js'

if ((Test-Path -LiteralPath $localNode) -and (Test-Path -LiteralPath $localNpm)) {
    $env:Path = (Split-Path -Parent $localNode) + ';' + $env:Path
    & $localNode $localNpm --prefix $PSScriptRoot run $Task
} else {
    & npm.cmd --prefix $PSScriptRoot run $Task
}
exit $LASTEXITCODE
