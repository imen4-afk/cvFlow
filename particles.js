(function () {
  var container = document.getElementById('bgParticles');
  if (!container) return;

  var pool = [
    '🦋','🌸','✨','⭐','🌟','🐱','🦊','🐰','🌺','💫',
    '🍀','🌼','🐧','🦄','🎀','🌈','🐼','💕','🌷','☁️'
  ];
  var anims = ['bpFloat1', 'bpFloat2', 'bpFloat3'];
  var COUNT = 15;

  for (var i = 0; i < COUNT; i++) {
    var el = document.createElement('span');
    el.className = 'bp';
    el.setAttribute('aria-hidden', 'true');
    el.textContent = pool[Math.floor(Math.random() * pool.length)];

    var size  = 16 + Math.floor(Math.random() * 26); // 16–42 px
    var left  = 2  + Math.random() * 96;              // 2–98 %
    var dur   = 15 + Math.random() * 20;              // 15–35 s
    var delay = -(Math.random() * dur);               // negative = already mid-flight on load
    var anim  = anims[Math.floor(Math.random() * anims.length)];

    el.style.cssText =
      'left:'       + left  + '%;' +
      'font-size:'  + size  + 'px;' +
      'animation:'  + anim  + ' ' + dur + 's ' + delay + 's linear infinite;';

    container.appendChild(el);
  }
}());
