<div>
  <a-scene>
    <a-assets>
      <img
        src="../../../../img/p5/2-1.png"
        timeout="10000"
        crossOrigin="anonymous"
        id="sun"
      />
      <img
        src="../../../../img/p5/2-2.png"
        crossOrigin="anonymous"
        id="mercury"
      />
      <img
        src="../../../../img/p5/2-3.png"
        crossOrigin="anonymous"
        id="venus"
      />
      <img
        src="../../../../img/p5/2-4.png"
        crossOrigin="anonymous"
        id="earth"
      />
      <img
        src="../../../../img/p5/3-1.png"
        crossOrigin="anonymous"
        id="mars"
      />
      <img
        src="../../../../img/p5/3-2.png"
        crossOrigin="anonymous"
        id="jupiter"
      />
      <img
        src="../../../../img/p5/3-3.png"
        crossOrigin="anonymous"
        id="uranus"
      />
      <img
        src="../../../../img/p5/3-4.png"
        crossOrigin="anonymous"
        id="neptune"
      />
      <img src="../../../../img/p5/2.png" crossOrigin="anonymous" id="2" />
      <img
        src="../../../../img/p5/1.png"
        crossOrigin="anonymous"
        id="space"
        height="480px"
        width="640px"
      />
      <audio
        loop
        preload="auto"
        id="air"
        src="https://cdn.aframe.io/basic-guide/audio/backgroundnoise.wav"
        crossOrigin="anonymous"
      ></audio>
      <video id="webcam" autoPlay crossOrigin="anonymous"></video>
    </a-assets>

    <a-sphere
      src="#sun"
      position="0 2 -12"
      radius="4"
      opacity="1"
      animation__rotation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
      animation__scale="property: scale; to: 1.1 1.1 1.1; dur: 1250; dir: alternate; loop: true"
    />

    <a-box
      src="#neptune"
      position="-5 12 -17"
      rotation="90 65 20"
      scale="2 2 2"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#neptune"
      position="-5 12 -17"
      rotation="10 20 30"
      scale="2"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#uranus"
      position="3 -3 -8"
      radius="1.5"
      opacity="1"
      animation="property: object3D.rotation.y; to: -360; dur: 10000; loop: true"
    />
    <a-box
      src="#uranus"
      position="3 -3 -8"
      rotation="10 20 30"
      scale="1.5 1.5 1.5"
      opacity="1"
      animation="property: object3D.rotation.y; to: -360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#jupiter"
      position="7 11 -15"
      radius="2"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-box
      src="#jupiter"
      position="7 11 -15"
      rotation="10 20 30"
      scale="3 3 2"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#jupiter"
      position="7 11 -15"
      rotation="82 75 27"
      scale="3 1 2.5"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#mercury"
      position="-1 0 -8"
      radius="0.25"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-box
      src="#mercury"
      position="-1 0 -8"
      rotation="10 20 30"
      scale="0.25 0.5 0.5"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#mercury"
      position="-1 0 -8"
      rotation="72 85 90"
      radius="0.15"
      scale="0.5 0.75 0.75"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#earth"
      position="-5.5 4 -12"
      radius="0.65"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-box
      src="#earth"
      position="-5.5 4 -12"
      rotation="10 20 30"
      scale="0.75 1.5 0.75"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#earth"
      position="-5.5 4 -12"
      rotation="72 85 90"
      radius="0.05"
      scale="1 4 3.5"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#venus"
      position="-1 -3 -8"
      radius="0.75"
      opacity="1"
      animation="property: object3D.rotation.y; to: -360; dur: 10000; loop: true"
    />
    <a-box
      src="#venus"
      position="-1 -3 -8"
      rotation="10 20 30"
      scale="0.75 1 1"
      opacity="1"
      animation="property: object3D.rotation.y; to: -360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#venus"
      position="-1 -3 -8"
      rotation="72 85 90"
      radius="0.15"
      scale="0.75 3 2.5"
      animation="property: object3D.rotation.y; to: -360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#mars"
      position="5.5 4 -12"
      radius="0.75"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-box
      src="#mars"
      position="5.5 4 -12"
      rotation="10 20 30"
      scale="0.75 1.5 2"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#mars"
      position="5.5 4 -12"
      rotation="72 85 90"
      radius="0.15"
      scale="0.75 3 2.5"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />

    <a-sphere
      src="#2"
      position="-5.5 -6 -12"
      radius="0.75"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-box
      src="#2"
      position="-5.5 -6 -12"
      rotation="10 20 30"
      scale="0.75 1.5 2"
      opacity="1"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />
    <a-cylinder
      src="#2"
      position="-5.5 -6 -12"
      rotation="72 85 90"
      radius="0.15"
      scale="0.75 3 2.5"
      animation="property: object3D.rotation.y; to: 360; dur: 10000; loop: true"
    />

    <a-sky src="#space" opacity="1" />

    <a-light type="point" intensity="1.25" position="2 4 4" />

    <a-sound src="#air", autoplay: true />
  </a-scene>
</div>