(function () {
    var box = document.querySelector('.link-fit');
    var paper = box && box.querySelector('.correspondence-paper');
    var printButton = document.querySelector('[data-link-print]');

    if (printButton) {
        printButton.addEventListener('click', function () {
            window.print();
        });
    }

    if (!paper) return;

    function fit() {
        paper.style.transform = 'none';
        var width = paper.offsetWidth;
        var room = document.documentElement.clientWidth - 16;
        if (!width || room <= 0) return;
        var scale = Math.min(1, room / width);
        paper.style.transformOrigin = document.documentElement.getAttribute('dir') === 'rtl' ? 'top right' : 'top left';
        paper.style.transform = scale < 1 ? 'scale(' + scale + ')' : 'none';
        box.style.height = scale < 1 ? Math.ceil(paper.offsetHeight * scale) + 'px' : '';
    }

    fit();
    window.addEventListener('load', fit);
    window.addEventListener('resize', fit);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
    if (window.ResizeObserver) new ResizeObserver(fit).observe(paper);
})();
