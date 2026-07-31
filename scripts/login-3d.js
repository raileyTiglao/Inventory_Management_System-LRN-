/**
 * Login page ambient animation — a rotating dashed Hilbert curve inside a
 * wireframe crate, echoing the blueprint / bin-tag warehouse motif.
 *
 * Adapted from three.js' webgl_lines_dashed example, with four changes that
 * the original (a full-page demo) doesn't account for:
 *   - sized to its container rather than the viewport, and re-sized via
 *     ResizeObserver instead of the window resize event;
 *   - drawn on a transparent canvas (no opaque scene background or fog) so
 *     the panel's own colour and blueprint grid show through;
 *   - recoloured to the app's amber tokens, which sit far enough from both
 *     the light and dark panel backgrounds to read in either theme;
 *   - the Stats dev overlay dropped.
 */

import * as THREE from 'three';
import * as GeometryUtils from 'three/addons/utils/GeometryUtils.js';

// --color-tag-amber / --color-tag-amberdark. These two are identical in both
// themes, so the animation needs no per-theme handling of its own.
const AMBER = 0xe8a33d;
const AMBER_DARK = 0xb87a22;

const container = document.getElementById('login-3d-bg');
if (container) init(container);

function init(container) {
  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  } catch (err) {
    // WebGL blocked or unavailable — the CSS blueprint grid behind this
    // canvas already carries the panel on its own, so just bail out.
    return;
  }

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, 1, 1, 400);
  camera.position.z = 150;

  const points = GeometryUtils.hilbert3D(new THREE.Vector3(0, 0, 0), 25.0, 1, 0, 1, 2, 3, 4, 5, 6, 7);
  const spline = new THREE.CatmullRomCurve3(points);
  const samples = spline.getPoints(points.length * 6);

  const curve = new THREE.Line(
    new THREE.BufferGeometry().setFromPoints(samples),
    new THREE.LineDashedMaterial({ color: AMBER, dashSize: 1, gapSize: 0.5, transparent: true, opacity: 0.75 })
  );
  curve.computeLineDistances();
  scene.add(curve);

  const crate = new THREE.LineSegments(
    box(50, 50, 50),
    new THREE.LineDashedMaterial({ color: AMBER_DARK, dashSize: 3, gapSize: 1, transparent: true, opacity: 0.55 })
  );
  crate.computeLineDistances();
  scene.add(crate);

  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setClearAlpha(0);
  container.appendChild(renderer.domElement);

  function resize() {
    // The panel is display:none below the md breakpoint, so the container
    // can legitimately measure 0 — skip rather than divide by it.
    const w = container.clientWidth;
    const h = container.clientHeight;
    if (!w || !h) return false;

    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
    return true;
  }

  function render(elapsed) {
    // Original demo used seconds (0.25 * time); setAnimationLoop hands us
    // milliseconds, so 0.00025 keeps the same rotation speed.
    const t = elapsed * 0.00025;
    curve.rotation.x = t;
    curve.rotation.y = t;
    crate.rotation.x = t;
    crate.rotation.y = t;
    renderer.render(scene, camera);
  }

  new ResizeObserver(resize).observe(container);

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    // Draw a single static frame once the panel has a measurable size.
    const paint = new ResizeObserver(() => {
      if (resize()) {
        render(0);
        paint.disconnect();
      }
    });
    paint.observe(container);
  } else {
    resize();
    renderer.setAnimationLoop(render);
  }
}

function box(width, height, depth) {
  const w = width * 0.5;
  const h = height * 0.5;
  const d = depth * 0.5;

  const geometry = new THREE.BufferGeometry();
  geometry.setAttribute('position', new THREE.Float32BufferAttribute([
    -w, -h, -d, -w, h, -d,
    -w, h, -d, w, h, -d,
    w, h, -d, w, -h, -d,
    w, -h, -d, -w, -h, -d,

    -w, -h, d, -w, h, d,
    -w, h, d, w, h, d,
    w, h, d, w, -h, d,
    w, -h, d, -w, -h, d,

    -w, -h, -d, -w, -h, d,
    -w, h, -d, -w, h, d,
    w, h, -d, w, h, d,
    w, -h, -d, w, -h, d,
  ], 3));

  return geometry;
}
