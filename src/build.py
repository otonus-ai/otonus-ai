# Wraps src/page.html into a deployable public/index.html (the preview artifact uses page.html directly).
import os
D = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = f"{D}/public/index.html"
page = open(f"{D}/src/page.html").read()
head = """<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
<meta property="og:title" content="Otonus.ai | AI Automation &amp; Intelligent Business Solutions">
<meta property="og:description" content="AI automation, business automation, web development and digital marketing.">
<meta property="og:image" content="https://otonus.ai/assets/og-image.jpg">
<meta property="og:url" content="https://otonus.ai/">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#cfe4ff">
"""
title_end = page.index("<style>")
out = head + page[:title_end] + page[title_end:page.index("</style>") + 8] + "\n</head>\n<body>\n" + page[page.index("</style>") + 8:] + "\n</body>\n</html>\n"
open(OUT, "w").write(out)
print("built", len(out))
