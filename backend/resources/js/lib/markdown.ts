function escapeHtml(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
}

function applyInline(text: string): string {
    return text
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/__(.+?)__/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/_(.+?)_/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
}

export function renderMarkdown(raw: string): string {
    const lines    = raw.split('\n')
    const out:     string[]  = []
    let inCode     = false
    let listType:  'ul' | 'ol' | null = null
    let paraLines: string[]  = []

    function flushPara() {
        if (paraLines.length) {
            out.push(`<p>${paraLines.join('<br>')}</p>`)
            paraLines = []
        }
    }

    function closeList() {
        if (listType) {
            out.push(listType === 'ul' ? '</ul>' : '</ol>')
            listType = null
        }
    }

    for (const line of lines) {
        if (line.trim().startsWith('```')) {
            if (inCode) {
                out.push('</code></pre>')
                inCode = false
            } else {
                flushPara()
                closeList()
                out.push('<pre><code>')
                inCode = true
            }
            continue
        }

        if (inCode) {
            out.push(escapeHtml(line))
            continue
        }

        if (line.trim() === '') {
            flushPara()
            closeList()
            continue
        }

        const h3 = line.match(/^### (.+)/)
        if (h3) { flushPara(); closeList(); out.push(`<h3>${applyInline(h3[1])}</h3>`); continue }
        const h2 = line.match(/^## (.+)/)
        if (h2) { flushPara(); closeList(); out.push(`<h2>${applyInline(h2[1])}</h2>`); continue }
        const h1 = line.match(/^# (.+)/)
        if (h1) { flushPara(); closeList(); out.push(`<h1>${applyInline(h1[1])}</h1>`); continue }

        const ul = line.match(/^[-*] (.+)/)
        if (ul) {
            flushPara()
            if (listType !== 'ul') { closeList(); out.push('<ul>'); listType = 'ul' }
            out.push(`<li>${applyInline(ul[1])}</li>`)
            continue
        }

        const ol = line.match(/^\d+\. (.+)/)
        if (ol) {
            flushPara()
            if (listType !== 'ol') { closeList(); out.push('<ol>'); listType = 'ol' }
            out.push(`<li>${applyInline(ol[1])}</li>`)
            continue
        }

        closeList()
        paraLines.push(applyInline(line))
    }

    flushPara()
    closeList()
    if (inCode) out.push('</code></pre>')

    return out.join('')
}
