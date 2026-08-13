<style>
  .copy-code-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
    background-color: rgba(255, 255, 255, 0.12);
    color: #94a3b8;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    padding: 3px 8px;
    font-size: 0.75rem;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;
  }
  .copy-code-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
    color: #ffffff;
  }
  .ai-markdown-content p {
    margin-bottom: 0.5rem;
  }
  .ai-markdown-content p:last-child {
    margin-bottom: 0;
  }
  .ai-markdown-content pre {
    background-color: #1e293b !important;
    color: #f8fafc !important;
    padding: 12px 16px !important;
    border-radius: 8px !important;
    overflow-x: auto !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 0.85rem !important;
    margin: 10px 0 !important;
    line-height: 1.5 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
  }
  .ai-markdown-content code {
    background-color: rgba(128, 128, 128, 0.18) !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 0.875em !important;
  }
  .ai-markdown-content pre code {
    background-color: transparent !important;
    padding: 0 !important;
    border-radius: 0 !important;
    color: inherit !important;
  }
  .ai-markdown-content ul {
    list-style-type: disc !important;
    padding-left: 1.25rem !important;
    margin: 8px 0 !important;
  }
  .ai-markdown-content ol {
    list-style-type: decimal !important;
    padding-left: 1.25rem !important;
    margin: 8px 0 !important;
  }
  .ai-markdown-content li {
    margin-bottom: 4px !important;
  }
  .ai-markdown-content h1, .ai-markdown-content h2, .ai-markdown-content h3, .ai-markdown-content h4 {
    font-weight: 600 !important;
    margin-top: 12px !important;
    margin-bottom: 6px !important;
  }
  .ai-markdown-content h1 { font-size: 1.25rem !important; }
  .ai-markdown-content h2 { font-size: 1.1rem !important; }
  .ai-markdown-content h3 { font-size: 1.0rem !important; }
  .ai-markdown-content blockquote {
    border-left: 4px solid #3b82f6;
    padding-left: 12px;
    margin: 8px 0;
    font-style: italic;
    opacity: 0.85;
  }
  .ai-markdown-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    font-size: 0.875rem;
  }
  .ai-markdown-content th, .ai-markdown-content td {
    border: 1px solid rgba(128, 128, 128, 0.2);
    padding: 6px 10px;
    text-align: left;
  }
  .ai-markdown-content th {
    background-color: rgba(128, 128, 128, 0.1);
  }
</style>
