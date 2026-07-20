import { Client } from '@notionhq/client';
import dotenv from 'dotenv';
import { existsSync, readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, isAbsolute, resolve } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const DEFAULT_SEED_FILE = 'notion-dashboard-seed.json';
const DEFAULT_DASHBOARD_TITLE = '🏠 AG Shop — Dashboard';
const MANAGED_SUMMARY_TITLE = '🧭 Dashboard Summary (managed)';
const DEV_TASKS_DB_TITLE = 'Dev Tasks';
const ROADMAP_DB_TITLE = 'Roadmap';
const FINAL_STATUSES = new Set(['Done', 'Canceled']);

function findEnvFile(startDir) {
  let dir = startDir;
  for (let i = 0; i < 10; i += 1) {
    const envPath = resolve(dir, '.env');
    if (existsSync(envPath)) return envPath;
    const parent = dirname(dir);
    if (parent === dir) break;
    dir = parent;
  }
  return null;
}

function parseBoolean(value, fallback = false) {
  if (value == null) return fallback;
  const normalized = String(value).trim().toLowerCase();
  if (['1', 'true', 'yes', 'y', 'on'].includes(normalized)) return true;
  if (['0', 'false', 'no', 'n', 'off'].includes(normalized)) return false;
  return fallback;
}

function normalizeNotionId(input) {
  if (!input) return null;
  const value = String(input).trim();
  const compact = value.match(/[0-9a-fA-F]{32}/);
  if (compact) {
    const id = compact[0].toLowerCase();
    return `${id.slice(0, 8)}-${id.slice(8, 12)}-${id.slice(12, 16)}-${id.slice(16, 20)}-${id.slice(20)}`;
  }

  const dashed = value.match(/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/);
  if (dashed) return dashed[0].toLowerCase();

  return value;
}

function resolveSeedPath(customPath) {
  if (!customPath) return resolve(__dirname, DEFAULT_SEED_FILE);
  return isAbsolute(customPath) ? customPath : resolve(process.cwd(), customPath);
}

function chunk(items, size = 100) {
  const out = [];
  for (let i = 0; i < items.length; i += size) out.push(items.slice(i, i + size));
  return out;
}

function text(content) {
  return { type: 'text', text: { content: String(content ?? '') } };
}

function heading2(content) {
  return {
    type: 'heading_2',
    heading_2: { rich_text: [text(content)] },
  };
}

function heading3(content) {
  return {
    type: 'heading_3',
    heading_3: { rich_text: [text(content)] },
  };
}

function paragraph(content, bold = false) {
  return {
    type: 'paragraph',
    paragraph: {
      rich_text: [{ type: 'text', text: { content: String(content ?? '') }, annotations: { bold } }],
    },
  };
}

function callout(emoji, color, content) {
  return {
    type: 'callout',
    callout: {
      rich_text: [text(content)],
      icon: { type: 'emoji', emoji },
      color,
    },
  };
}

function bulletItem(content) {
  return {
    type: 'bulleted_list_item',
    bulleted_list_item: { rich_text: [text(content)] },
  };
}

function numberedItem(content) {
  return {
    type: 'numbered_list_item',
    numbered_list_item: { rich_text: [text(content)] },
  };
}

function columnList(columns) {
  return {
    type: 'column_list',
    column_list: {},
    children: columns.map((blocks) => ({
      type: 'column',
      column: {},
      children: blocks,
    })),
  };
}

function richTextToPlain(richText = []) {
  return richText.map((item) => item?.plain_text ?? item?.text?.content ?? '').join('').trim();
}

function getTitlePropertyName(database) {
  for (const [name, definition] of Object.entries(database.properties ?? {})) {
    if (definition?.type === 'title') return name;
  }
  return null;
}

function normalizeTitle(value) {
  return String(value ?? '').trim().toLowerCase();
}

function toISODate(date) {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function parseDate(value) {
  if (!value) return null;
  const parsed = new Date(`${value}T00:00:00`);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function startOfDay(date) {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

function endOfDay(date) {
  const d = new Date(date);
  d.setHours(23, 59, 59, 999);
  return d;
}

function progressBarFromPercent(percent, total = 10) {
  const normalized = Math.max(0, Math.min(100, Math.round(percent)));
  const filled = Math.round((normalized / 100) * total);
  return `${'▓'.repeat(filled)}${'░'.repeat(total - filled)} ${normalized}%`;
}

function selectProp(value) {
  return value ? { select: { name: value } } : { select: null };
}

function dateProp(value) {
  return value ? { date: { start: value } } : { date: null };
}

function numberProp(value) {
  return Number.isFinite(value) ? { number: value } : { number: null };
}

function richTextProp(value) {
  return value ? { rich_text: [text(value)] } : { rich_text: [] };
}

function relationProp(ids = []) {
  return { relation: ids.filter(Boolean).map((id) => ({ id })) };
}

function getSelectName(page, propName) {
  return page.properties?.[propName]?.select?.name ?? null;
}

function getDateStart(page, propName) {
  return page.properties?.[propName]?.date?.start ?? null;
}

function getRelationIds(page, propName) {
  return (page.properties?.[propName]?.relation ?? []).map((item) => item.id);
}

function getRetryAfterMs(err) {
  const headers = err?.headers ?? {};
  const retryAfterRaw = headers['retry-after'] ?? headers['Retry-After'] ?? err?.body?.retry_after;
  const retryAfterNum = Number(retryAfterRaw);
  if (!Number.isFinite(retryAfterNum) || retryAfterNum <= 0) return null;
  return retryAfterNum * 1000;
}

function getErrorStatus(err) {
  return Number(err?.status ?? err?.statusCode ?? err?.body?.status ?? NaN);
}

function isRetryable(err) {
  const status = getErrorStatus(err);
  return err?.code === 'rate_limited' || status === 429 || (status >= 500 && status < 600);
}

async function sleep(ms) {
  return new Promise((resolveSleep) => setTimeout(resolveSleep, ms));
}

const envPath = findEnvFile(__dirname);
if (!envPath) {
  console.error('Could not find .env file');
  process.exit(1);
}

dotenv.config({ path: envPath, quiet: true });

const {
  NOTION_TOKEN,
  NOTION_PAGE_ID,
  NOTION_DASHBOARD_PAGE_ID,
  NOTION_DASHBOARD_TITLE,
  NOTION_DASHBOARD_SEED_PATH,
  NOTION_ALLOW_WORKSPACE_FALLBACK,
} = process.env;

if (!NOTION_TOKEN || (!NOTION_PAGE_ID && !NOTION_DASHBOARD_PAGE_ID)) {
  console.error('Missing required env vars: NOTION_TOKEN and one of NOTION_PAGE_ID or NOTION_DASHBOARD_PAGE_ID');
  process.exit(1);
}

const dashboardTitle = NOTION_DASHBOARD_TITLE || DEFAULT_DASHBOARD_TITLE;
const allowWorkspaceFallback = parseBoolean(NOTION_ALLOW_WORKSPACE_FALLBACK, false);
const parentPageId = normalizeNotionId(NOTION_PAGE_ID);
const configuredDashboardPageId = normalizeNotionId(NOTION_DASHBOARD_PAGE_ID);
const seedPath = resolveSeedPath(NOTION_DASHBOARD_SEED_PATH);

if (!existsSync(seedPath)) {
  console.error(`Seed file not found: ${seedPath}`);
  process.exit(1);
}

let seed;
try {
  seed = JSON.parse(readFileSync(seedPath, 'utf8'));
} catch (err) {
  console.error(`Failed to parse seed JSON at ${seedPath}`);
  console.error(err);
  process.exit(1);
}

const notion = new Client({ auth: NOTION_TOKEN });

async function withRetry(label, fn, maxAttempts = 6) {
  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    try {
      return await fn();
    } catch (err) {
      if (attempt >= maxAttempts || !isRetryable(err)) throw err;
      const retryAfter = getRetryAfterMs(err);
      const backoff = retryAfter ?? Math.min(1000 * 2 ** (attempt - 1), 12000) + Math.floor(Math.random() * 250);
      console.log(`  ${label} failed (attempt ${attempt}/${maxAttempts}). Retrying in ${backoff}ms...`);
      await sleep(backoff);
    }
  }

  throw new Error(`Unexpected retry exhaustion while running ${label}`);
}

async function appendBlocks(blockId, children) {
  let lastResponse = null;
  for (const childrenChunk of chunk(children, 100)) {
    lastResponse = await withRetry(`append blocks to ${blockId}`, () => notion.blocks.children.append({
      block_id: blockId,
      children: childrenChunk,
    }));
  }
  return lastResponse;
}

async function listAllBlockChildren(blockId) {
  const children = [];
  let cursor;
  do {
    const response = await withRetry(`list block children ${blockId}`, () => notion.blocks.children.list({
      block_id: blockId,
      page_size: 100,
      start_cursor: cursor,
    }));
    children.push(...response.results);
    cursor = response.has_more ? response.next_cursor : undefined;
  } while (cursor);
  return children;
}

async function queryAllDatabasePages(databaseId) {
  const pages = [];
  let cursor;
  do {
    const response = await withRetry(`query database ${databaseId}`, () => notion.databases.query({
      database_id: databaseId,
      page_size: 100,
      start_cursor: cursor,
    }));
    pages.push(...response.results);
    cursor = response.has_more ? response.next_cursor : undefined;
  } while (cursor);
  return pages;
}

function indexPagesByTitle(pages, titlePropertyName) {
  const byTitle = new Map();
  const duplicates = [];

  for (const page of pages) {
    const title = richTextToPlain(page.properties?.[titlePropertyName]?.title ?? []);
    const key = normalizeTitle(title);
    if (!key) continue;

    if (!byTitle.has(key)) {
      byTitle.set(key, page);
    } else {
      duplicates.push(page);
    }
  }

  return { byTitle, duplicates };
}

async function archivePages(pages, reason) {
  if (!pages.length) return;

  for (const page of pages) {
    await withRetry(`archive duplicate ${reason} page ${page.id}`, () => notion.pages.update({
      page_id: page.id,
      archived: true,
    }));
  }

  console.log(`  Archived ${pages.length} duplicate ${reason} page(s).`);
}

async function getPageIfAccessible(pageId) {
  try {
    return await withRetry(`retrieve page ${pageId}`, () => notion.pages.retrieve({ page_id: pageId }));
  } catch (err) {
    if (err?.code === 'object_not_found') return null;
    throw err;
  }
}

async function findDashboardPageUnderParent(parentId, title) {
  let children;
  try {
    children = await listAllBlockChildren(parentId);
  } catch (err) {
    if (err?.code === 'object_not_found' && allowWorkspaceFallback) {
      console.log('  Parent page is not accessible while checking existing dashboard. Skipping parent lookup.');
      return null;
    }
    throw err;
  }
  const childPage = children.find((block) => block.type === 'child_page' && block.child_page?.title === title);
  if (!childPage) return null;
  return withRetry(`retrieve dashboard page ${childPage.id}`, () => notion.pages.retrieve({ page_id: childPage.id }));
}

async function createDashboardPage(title) {
  if (parentPageId) {
    try {
      return await withRetry('create dashboard page under parent', () => notion.pages.create({
        parent: { page_id: parentPageId },
        properties: { title: { title: [text(title)] } },
      }));
    } catch (err) {
      if (allowWorkspaceFallback && err?.code === 'object_not_found') {
        console.log('  Parent page not accessible. Falling back to workspace root (allowed by env flag).');
      } else {
        throw new Error(
          `Could not create dashboard under NOTION_PAGE_ID. ${
            allowWorkspaceFallback
              ? 'Parent is inaccessible even with fallback enabled.'
              : 'Set NOTION_ALLOW_WORKSPACE_FALLBACK=true to allow workspace-root creation.'
          }`
        );
      }
    }
  }

  if (!allowWorkspaceFallback) {
    throw new Error('Dashboard page does not exist and workspace fallback is disabled.');
  }

  return withRetry('create dashboard page at workspace root', () => notion.pages.create({
    parent: { workspace: true },
    properties: { title: { title: [text(title)] } },
  }));
}

async function ensureDashboardPage() {
  if (configuredDashboardPageId) {
    const existingById = await getPageIfAccessible(configuredDashboardPageId);
    if (existingById) {
      console.log(`  Using existing dashboard from NOTION_DASHBOARD_PAGE_ID: ${existingById.id}`);
      return existingById;
    }
    console.log('  NOTION_DASHBOARD_PAGE_ID is not accessible. Falling back to lookup/create logic.');
  }

  if (parentPageId) {
    const existingByTitle = await findDashboardPageUnderParent(parentPageId, dashboardTitle);
    if (existingByTitle) {
      console.log(`  Reusing existing dashboard under parent: ${existingByTitle.id}`);
      return existingByTitle;
    }
  }

  const created = await createDashboardPage(dashboardTitle);
  console.log(`  Created new dashboard page: ${created.id}`);
  return created;
}

async function ensureChildDatabase(pageId, databaseTitle) {
  const children = await listAllBlockChildren(pageId);
  const existing = children.find((block) => block.type === 'child_database' && block.child_database?.title === databaseTitle);
  if (existing) {
    console.log(`  Reusing database: ${databaseTitle}`);
    return existing.id;
  }

  const created = await withRetry(`create database ${databaseTitle}`, () => notion.databases.create({
    parent: { type: 'page_id', page_id: pageId },
    title: [{ type: 'text', text: { content: databaseTitle } }],
    properties: {
      Name: { title: {} },
    },
  }));
  const dbId = created?.id;
  if (!dbId) throw new Error(`Failed to create database ${databaseTitle}`);
  console.log(`  Created database: ${databaseTitle}`);
  return dbId;
}

async function ensureDatabaseSchemas(taskDbId, roadmapDbId) {
  await withRetry('update roadmap schema', () => notion.databases.update({
    database_id: roadmapDbId,
    properties: {
      Description: { rich_text: {} },
      Release: {
        select: {
          options: [
            { name: 'v1.0', color: 'blue' },
            { name: 'v1.1', color: 'purple' },
            { name: 'v1.2', color: 'orange' },
            { name: 'v1.3', color: 'green' },
          ],
        },
      },
      Status: {
        select: {
          options: [
            { name: 'Planned', color: 'gray' },
            { name: 'In Progress', color: 'yellow' },
            { name: 'Completed', color: 'green' },
            { name: 'Delayed', color: 'red' },
          ],
        },
      },
      Owner: { people: {} },
      'Start Date': { date: {} },
      'Target Date': { date: {} },
      'Done Date': { date: {} },
      Tasks: { relation: { database_id: taskDbId, single_property: {} } },
      '% Done': { number: { format: 'percent' } },
      'Open Blockers': { number: { format: 'number' } },
      'Days Late': {
        formula: {
          expression: 'if(not empty(prop("Target Date")) and prop("Status") != "Completed" and prop("Target Date") < now(), dateBetween(now(), prop("Target Date"), "days"), 0)',
        },
      },
    },
  }));

  await withRetry('update task schema', () => notion.databases.update({
    database_id: taskDbId,
    properties: {
      Status: {
        select: {
          options: [
            { name: 'Not Started', color: 'gray' },
            { name: 'In Progress', color: 'yellow' },
            { name: 'In Review', color: 'blue' },
            { name: 'Done', color: 'green' },
            { name: 'Blocked', color: 'red' },
            { name: 'Canceled', color: 'pink' },
          ],
        },
      },
      Priority: {
        select: {
          options: [
            { name: 'P0', color: 'red' },
            { name: 'P1', color: 'orange' },
            { name: 'P2', color: 'yellow' },
            { name: 'P3', color: 'gray' },
          ],
        },
      },
      Release: {
        select: {
          options: [
            { name: 'v1.0', color: 'blue' },
            { name: 'v1.1', color: 'purple' },
            { name: 'v1.2', color: 'orange' },
            { name: 'v1.3', color: 'green' },
          ],
        },
      },
      Owner: { people: {} },
      Milestone: { relation: { database_id: roadmapDbId, single_property: {} } },
      'Blocked By': { relation: { database_id: taskDbId, single_property: {} } },
      'Blocked Reason': { rich_text: {} },
      'Estimate (pts)': { number: { format: 'number' } },
      'Actual (pts)': { number: { format: 'number' } },
      'Start Date': { date: {} },
      'Due Date': { date: {} },
      'Done Date': { date: {} },
      Overdue: {
        formula: {
          expression: 'not empty(prop("Due Date")) and prop("Status") != "Done" and prop("Status") != "Canceled" and prop("Due Date") < now()',
        },
      },
    },
  }));
}

async function getDatabaseTitleProperty(databaseId) {
  const db = await withRetry(`retrieve database ${databaseId}`, () => notion.databases.retrieve({ database_id: databaseId }));
  const titlePropertyName = getTitlePropertyName(db);
  if (!titlePropertyName) throw new Error(`Could not resolve title property for database ${databaseId}`);
  return titlePropertyName;
}

async function upsertMilestones(roadmapDbId, titlePropName, milestones) {
  const existingPages = await queryAllDatabasePages(roadmapDbId);
  const { byTitle, duplicates } = indexPagesByTitle(existingPages, titlePropName);
  await archivePages(duplicates, 'roadmap');

  const milestoneIdByTitle = new Map();

  for (const milestone of milestones) {
    const normalized = normalizeTitle(milestone.title);
    const properties = {
      [titlePropName]: { title: [text(milestone.title)] },
      Description: richTextProp(milestone.description),
      Release: selectProp(milestone.release),
      Status: selectProp(milestone.status),
      'Start Date': dateProp(milestone.start ?? null),
      'Target Date': dateProp(milestone.target),
      'Done Date': dateProp(milestone.done ?? null),
    };

    const existing = byTitle.get(normalized);
    if (existing) {
      await withRetry(`update milestone ${milestone.title}`, () => notion.pages.update({
        page_id: existing.id,
        properties,
      }));
      milestoneIdByTitle.set(milestone.title, existing.id);
    } else {
      const created = await withRetry(`create milestone ${milestone.title}`, () => notion.pages.create({
        parent: { database_id: roadmapDbId },
        properties,
      }));
      milestoneIdByTitle.set(milestone.title, created.id);
    }
  }

  return milestoneIdByTitle;
}

async function upsertTasks(taskDbId, titlePropName, tasks, milestoneIdByTitle) {
  const todayIso = toISODate(new Date());
  const existingPages = await queryAllDatabasePages(taskDbId);
  const { byTitle, duplicates } = indexPagesByTitle(existingPages, titlePropName);
  await archivePages(duplicates, 'task');

  const taskIdByTitle = new Map();

  for (const task of tasks) {
    const normalized = normalizeTitle(task.title);
    const milestoneId = task.milestone ? milestoneIdByTitle.get(task.milestone) : null;
    const doneDate = task.status === 'Done' ? (task.done ?? todayIso) : null;

    const properties = {
      [titlePropName]: { title: [text(task.title)] },
      Status: selectProp(task.status),
      Priority: selectProp(task.priority),
      Release: selectProp(task.release),
      Milestone: relationProp(milestoneId ? [milestoneId] : []),
      'Blocked By': relationProp([]),
      'Blocked Reason': richTextProp(task.blockedReason),
      'Estimate (pts)': numberProp(task.estimate),
      'Actual (pts)': numberProp(task.actual),
      'Start Date': dateProp(task.start ?? null),
      'Due Date': dateProp(task.due ?? null),
      'Done Date': dateProp(doneDate),
    };

    const existing = byTitle.get(normalized);
    if (existing) {
      await withRetry(`update task ${task.title}`, () => notion.pages.update({
        page_id: existing.id,
        properties,
      }));
      taskIdByTitle.set(task.title, existing.id);
    } else {
      const created = await withRetry(`create task ${task.title}`, () => notion.pages.create({
        parent: { database_id: taskDbId },
        properties,
      }));
      taskIdByTitle.set(task.title, created.id);
    }
  }

  for (const task of tasks) {
    const taskId = taskIdByTitle.get(task.title);
    if (!taskId) continue;

    const blockedIds = (task.blockedBy ?? [])
      .map((title) => taskIdByTitle.get(title))
      .filter(Boolean);

    await withRetry(`update task blockers for ${task.title}`, () => notion.pages.update({
      page_id: taskId,
      properties: {
        'Blocked By': relationProp(blockedIds),
      },
    }));
  }

  return taskIdByTitle;
}

function computeTaskMetrics(taskPages) {
  const now = new Date();
  const today = startOfDay(now);
  const weekAgo = startOfDay(new Date(now.getTime() - 6 * 24 * 60 * 60 * 1000));
  const nextWeek = endOfDay(new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000));

  let doneThisWeek = 0;
  let overdue = 0;
  let blocked = 0;
  let dueInNext7Days = 0;
  let openTasks = 0;

  for (const page of taskPages) {
    const status = getSelectName(page, 'Status');
    const dueDate = parseDate(getDateStart(page, 'Due Date'));
    const doneDate = parseDate(getDateStart(page, 'Done Date'));

    if (!FINAL_STATUSES.has(status)) openTasks += 1;
    if (status === 'Blocked') blocked += 1;

    if (doneDate && status === 'Done' && doneDate >= weekAgo && doneDate <= endOfDay(now)) {
      doneThisWeek += 1;
    }

    if (dueDate && dueDate < today && !FINAL_STATUSES.has(status)) {
      overdue += 1;
    }

    if (dueDate && dueDate >= today && dueDate <= nextWeek && !FINAL_STATUSES.has(status)) {
      dueInNext7Days += 1;
    }
  }

  return {
    doneThisWeek,
    overdue,
    blocked,
    dueInNext7Days,
    openTasks,
  };
}

function computeReleaseProgress(taskPages, releaseConfig) {
  return releaseConfig.map((release) => {
    const rows = taskPages.filter((page) => getSelectName(page, 'Release') === release.key);
    const total = rows.length;
    const done = rows.filter((page) => getSelectName(page, 'Status') === 'Done').length;
    const blocked = rows.filter((page) => getSelectName(page, 'Status') === 'Blocked').length;
    const progress = total ? Math.round((done / total) * 100) : 0;

    return {
      ...release,
      total,
      done,
      blocked,
      progress,
      bar: progressBarFromPercent(progress),
    };
  });
}

async function updateMilestoneRollups(roadmapDbId, milestoneIdByTitle, taskPages) {
  const statsByMilestoneId = new Map();

  for (const page of taskPages) {
    const milestoneIds = getRelationIds(page, 'Milestone');
    const status = getSelectName(page, 'Status');

    for (const milestoneId of milestoneIds) {
      if (!statsByMilestoneId.has(milestoneId)) {
        statsByMilestoneId.set(milestoneId, { total: 0, done: 0, blocked: 0, taskIds: [] });
      }

      const stats = statsByMilestoneId.get(milestoneId);
      stats.total += 1;
      if (status === 'Done') stats.done += 1;
      if (status === 'Blocked') stats.blocked += 1;
      stats.taskIds.push(page.id);
    }
  }

  for (const milestoneId of milestoneIdByTitle.values()) {
    const stats = statsByMilestoneId.get(milestoneId) ?? { total: 0, done: 0, blocked: 0, taskIds: [] };
    const percentDone = stats.total ? Math.round((stats.done / stats.total) * 100) : 0;

    await withRetry(`update milestone rollups ${milestoneId}`, () => notion.pages.update({
      page_id: milestoneId,
      properties: {
        Tasks: relationProp(stats.taskIds),
        '% Done': numberProp(percentDone / 100),
        'Open Blockers': numberProp(stats.blocked),
      },
    }));
  }

  const milestonePages = await queryAllDatabasePages(roadmapDbId);
  return milestonePages;
}

function buildSummaryChildren(seedData, health, releaseProgress, milestonePages) {
  const currentFocus = seedData.currentFocus ?? [];
  const recommendations = seedData.recommendations ?? [];

  const delayedMilestones = milestonePages.filter((page) => {
    const status = getSelectName(page, 'Status');
    const daysLate = page.properties?.['Days Late']?.formula?.number ?? 0;
    return status === 'Delayed' || daysLate > 0;
  }).length;

  const children = [
    heading2('🩺 Weekly Health Snapshot'),
    callout('✅', 'green_background', `Done in last 7 days: ${health.doneThisWeek}`),
    callout('📅', 'yellow_background', `Due in next 7 days: ${health.dueInNext7Days}`),
    callout('⚠️', 'red_background', `Blocked tasks: ${health.blocked} · Overdue tasks: ${health.overdue}`),
    callout('🧱', 'gray_background', `Open tasks: ${health.openTasks} · Delayed/late milestones: ${delayedMilestones}`),
    heading2('📌 Current Focus'),
  ];

  for (const focus of currentFocus) {
    children.push(callout(focus.emoji ?? '🔹', focus.color ?? 'blue_background', focus.text ?? ''));
  }

  children.push(heading2('📊 Release Progress'));
  for (const release of releaseProgress) {
    children.push(paragraph(`${release.key} — ${release.name}`, true));
    children.push(paragraph(release.bar));
    children.push(paragraph(`${release.done}/${release.total} done · ${release.blocked} blocked`));

    for (const highlight of release.highlights ?? []) {
      children.push(bulletItem(highlight));
    }

    if (release.target) {
      children.push(paragraph(`Target: ${release.target}`));
    }
  }

  children.push(heading2('💡 Recommendations'));
  for (const recommendation of recommendations) {
    children.push(numberedItem(recommendation));
  }

  children.push(
    heading3('How to use this dashboard weekly'),
    bulletItem('Update task status and done dates daily; review blockers each morning.'),
    bulletItem('Prioritize overdue and next-7-day tasks in standup.'),
    bulletItem('Review delayed milestones weekly and adjust scope or due dates explicitly.'),
  );

  return children;
}

function getToggleTitle(block) {
  if (block.type !== 'toggle') return null;
  return richTextToPlain(block.toggle?.rich_text ?? []);
}

async function replaceManagedSummaryBlock(pageId, seedData, health, releaseProgress, milestonePages) {
  const children = await listAllBlockChildren(pageId);
  const managedBlocks = children.filter((block) => getToggleTitle(block) === MANAGED_SUMMARY_TITLE);

  for (const block of managedBlocks) {
    await withRetry(`delete old managed summary ${block.id}`, () => notion.blocks.delete({ block_id: block.id }));
  }

  const summaryChildren = buildSummaryChildren(seedData, health, releaseProgress, milestonePages);

  const res = await appendBlocks(pageId, [{
    type: 'toggle',
    toggle: { rich_text: [text(MANAGED_SUMMARY_TITLE)] },
  }]);

  const toggleId = res?.results?.[0]?.id;
  if (toggleId && summaryChildren.length > 0) {
    await appendBlocks(toggleId, summaryChildren);
  }
}

function validateSeed(seedData) {
  if (!Array.isArray(seedData.tasks) || !Array.isArray(seedData.milestones)) {
    throw new Error('Seed JSON must include arrays: tasks and milestones.');
  }

  if (!Array.isArray(seedData.releases) || seedData.releases.length === 0) {
    throw new Error('Seed JSON must include a non-empty releases array.');
  }
}

async function main() {
  console.log('Creating/updating AG Shop dashboard page...');
  console.log(`  Seed file: ${seedPath}`);

  validateSeed(seed);

  const dashboardPage = await ensureDashboardPage();
  const pageId = dashboardPage.id;

  const roadmapDbId = await ensureChildDatabase(pageId, ROADMAP_DB_TITLE);
  const taskDbId = await ensureChildDatabase(pageId, DEV_TASKS_DB_TITLE);

  await ensureDatabaseSchemas(taskDbId, roadmapDbId);

  const roadmapTitleProp = await getDatabaseTitleProperty(roadmapDbId);
  const taskTitleProp = await getDatabaseTitleProperty(taskDbId);

  const milestoneIdByTitle = await upsertMilestones(roadmapDbId, roadmapTitleProp, seed.milestones);
  await upsertTasks(taskDbId, taskTitleProp, seed.tasks, milestoneIdByTitle);

  const latestTaskPages = await queryAllDatabasePages(taskDbId);
  const milestonePages = await updateMilestoneRollups(roadmapDbId, milestoneIdByTitle, latestTaskPages);

  const health = computeTaskMetrics(latestTaskPages);
  const releaseProgress = computeReleaseProgress(latestTaskPages, seed.releases);

  await replaceManagedSummaryBlock(pageId, seed, health, releaseProgress, milestonePages);

  const url = `https://notion.so/${pageId.replace(/-/g, '')}`;
  console.log('\n✅ Dashboard synchronized successfully!');
  console.log(`   ${url}`);
}

main().catch((err) => {
  console.error('Error:', err?.body ?? err);
  process.exit(1);
});
