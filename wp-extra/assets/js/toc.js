document.addEventListener('DOMContentLoaded', () => {
  const tocContainer = document.querySelector('.mce-toc');
  if (tocContainer) {
    const tocList = tocContainer.querySelector('ul');
    const contentsButton = tocContainer.querySelector('h3');
    contentsButton.innerHTML += ' <span>-</span>';
    tocContainer.querySelectorAll('a[href^="#"]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelector(link.hash).scrollIntoView({ behavior: 'smooth' });
        tocContainer.querySelectorAll('a').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
      });
    });
    window.addEventListener('scroll', () => {
      const scrollY = window.scrollY;
      tocContainer.querySelectorAll('a[href^="#"]').forEach(link => {
        const target = document.querySelector(link.hash);
        if (target && scrollY >= target.offsetTop - window.innerHeight / 3 && scrollY < target.offsetTop + target.offsetHeight) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      });
    });
    contentsButton.addEventListener('click', () => {
      const isHidden = tocList.style.display === 'none';
      tocList.style.display = isHidden ? 'block' : 'none';
      contentsButton.querySelector('span').textContent = isHidden ? '-' : '+';
    });
  }
});
