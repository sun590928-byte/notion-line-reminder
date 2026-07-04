#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
每天讀取 Notion「任務」資料庫，把快到期/已逾期的任務整理成訊息推到 LINE。
需要三個環境變數（在 GitHub Actions 的 Secrets 裡設定）：
  NOTION_TOKEN            - Notion Internal Integration Token
  NOTION_DATABASE_ID      - 「任務」資料庫的 ID
  LINE_CHANNEL_TOKEN      - LINE Messaging API 的 Channel Access Token
  LINE_USER_ID            - 要推播給誰的 LINE User ID（你自己的）
"""

import os
import sys
import requests
from datetime import datetime, timedelta

NOTION_TOKEN = os.environ["NOTION_TOKEN"]
NOTION_DATABASE_ID = os.environ["NOTION_DATABASE_ID"]
LINE_CHANNEL_TOKEN = os.environ["LINE_CHANNEL_TOKEN"]
LINE_USER_ID = os.environ["LINE_USER_ID"]

NOTION_VERSION = "2022-06-28"
LOOKAHEAD_DAYS = 2  # 未來幾天內到期都算「快到期」

# 視為「已結束、不用提醒」的狀態
DONE_STATUSES = {"完成", "已封存"}


def query_notion_tasks():
    url = f"https://api.notion.com/v1/databases/{NOTION_DATABASE_ID}/query"
    headers = {
        "Authorization": f"Bearer {NOTION_TOKEN}",
        "Notion-Version": NOTION_VERSION,
        "Content-Type": "application/json",
    }

    today = datetime.utcnow().date()
    deadline = today + timedelta(days=LOOKAHEAD_DAYS)

    payload = {
        "filter": {
            "and": [
                {
                    "property": "截止時間",
                    "date": {"on_or_before": deadline.isoformat()},
                },
                {
                    "property": "截止時間",
                    "date": {"is_not_empty": True},
                },
            ]
        },
        "sorts": [{"property": "截止時間", "direction": "ascending"}],
    }

    results = []
    has_more = True
    start_cursor = None
    while has_more:
        body = dict(payload)
        if start_cursor:
            body["start_cursor"] = start_cursor
        resp = requests.post(url, headers=headers, json=body, timeout=30)
        resp.raise_for_status()
        data = resp.json()
        results.extend(data.get("results", []))
        has_more = data.get("has_more", False)
        start_cursor = data.get("next_cursor")

    return results


def get_status(page):
    prop = page["properties"].get("狀態", {})
    status = prop.get("status")
    return status.get("name") if status else None


def get_title(page):
    prop = page["properties"].get("任務名稱", {})
    title_list = prop.get("title", [])
    if not title_list:
        return "(未命名任務)"
    return "".join([t.get("plain_text", "") for t in title_list])


def get_client(page):
    prop = page["properties"].get("客戶案件", {})
    select = prop.get("select")
    return select.get("name") if select else None


def get_due(page):
    prop = page["properties"].get("截止時間", {})
    date_obj = prop.get("date")
    return date_obj.get("start") if date_obj else None


def build_message(pages):
    today = datetime.utcnow().date()
    lines = []
    for page in pages:
        status = get_status(page)
        if status in DONE_STATUSES:
            continue
        title = get_title(page)
        client = get_client(page) or "未分類"
        due = get_due(page)
        due_date = due.split("T")[0] if due else "?"
        try:
            is_overdue = datetime.fromisoformat(due_date).date() < today
        except ValueError:
            is_overdue = False
        tag = "⚠️逾期" if is_overdue else "⏰"
        lines.append(f"{tag} [{client}] {title}（{due_date}）")

    if not lines:
        return None

    header = f"📋 任務提醒（{today.isoformat()}）\n未來 {LOOKAHEAD_DAYS} 天內到期 / 逾期任務：\n"
    return header + "\n".join(lines)


def push_line_message(message):
    url = "https://api.line.me/v2/bot/message/push"
    headers = {
        "Authorization": f"Bearer {LINE_CHANNEL_TOKEN}",
        "Content-Type": "application/json",
    }
    payload = {
        "to": LINE_USER_ID,
        "messages": [{"type": "text", "text": message}],
    }
    resp = requests.post(url, headers=headers, json=payload, timeout=30)
    resp.raise_for_status()


def main():
    pages = query_notion_tasks()
    message = build_message(pages)
    if message is None:
        print("沒有快到期或逾期的任務，不推播。")
        return
    print(message)
    push_line_message(message)
    print("已推播到 LINE。")


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(f"執行失敗：{e}", file=sys.stderr)
        sys.exit(1)
