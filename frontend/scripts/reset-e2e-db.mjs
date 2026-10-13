import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../..', import.meta.url))
const env = readFileSync(`${root}/.env.e2e`, 'utf8')
const database = env.match(/^DB_DATABASE=(.*)$/m)?.[1]?.trim() ?? ''

if (!database.endsWith('_e2e')) {
  throw new Error(`Menolak reset: DB_DATABASE di .env.e2e ("${database}") tidak berakhiran _e2e.`)
}

execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force', '--env=e2e'], {
  cwd: root,
  stdio: 'inherit',
})
