// cfp.js
document.addEventListener('DOMContentLoaded', () => {
    // テキストコピー用
    const copyTxtBtn = document.getElementById('copyTxtBtn');
    const previewContent = document.getElementById('previewContent');

    if (copyTxtBtn && previewContent) {
        copyTxtBtn.addEventListener('click', () => {
            const textToCopy = previewContent.innerText; // モーダル内のテキスト
            navigator.clipboard.writeText(textToCopy)
                .then(() => {
                    alert('テキストをコピーしました！');
                })
                .catch(err => {
                    console.error('コピー失敗:', err);
                });
        });
    }

    // 現在のURLコピー用
    const copyUrlBtn = document.getElementById('copyUrlBtn');
    if (copyUrlBtn) {
        copyUrlBtn.addEventListener('click', () => {
            const currentUrl = window.location.href;
            navigator.clipboard.writeText(currentUrl)
                .then(() => {
                    alert('URLをコピーしました！');
                })
                .catch(err => {
                    console.error('URLコピー失敗:', err);
                });
        });
    }
});
