{{-- resources/views/admin/camera.blade.php --}}
@extends('adminlte::page')

@section('title', 'Face Recognition | ' . Config::get('adminlte.title'))

@section('content_header')
    <h1>Face Recognition Attendance</h1>
@stop

@section('content')
    @include('layouts.flash-message')

    {{-- CSRF Token for AJAX requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- Video + Overlay Container -->
                <div id="cameraContainer" class="card-body p-0" style="height: 70vh;">
                    <video id="video"
                           autoplay
                           muted
                           playsinline
                           class="w-100 h-100 object-fit-cover"
                           style="border: 1px solid #CCC;">
                    </video>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="col-12 text-center mb-3">
            <button id="checkInButton" class="btn btn-success btn-lg mr-2">
                <i class="fas fa-sign-in-alt"></i> Check In
            </button>
            <button id="checkOutButton" class="btn btn-danger btn-lg ml-2">
                <i class="fas fa-sign-out-alt"></i> Check Out
            </button>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Ensure the bounding-box canvas appears on top of the video */
        #cameraContainer {
            position: relative;
            overflow: hidden;
        }
        #cameraContainer video {
            position: relative;
            z-index: 1;
        }
        #cameraContainer canvas {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 2;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /************************************************************
         * GLOBAL VARIABLES
         ************************************************************/
        let faceMatcher = null;
        let labeledDescriptors = [];
        let modelsLoaded = false;
        let liveTrackerInterval = null;

        // Avoid repeated fetch for face descriptors (cache them)
        let descriptorsCached = false;

        // Limit how often we run face detection (in ms)
        const DETECTION_COOLDOWN_MS = 2000;
        let lastDetectionTime = 0;

        /************************************************************
         * 1) START CAMERA
         ************************************************************/
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                document.getElementById('video').srcObject = stream;
                console.log("✅ Camera started.");
            } catch (error) {
                console.error("❌ Camera error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Camera Error',
                    text: 'Could not access camera. Check permissions.'
                });
            }
        }

        /************************************************************
         * 2) LOAD face-api.js MODELS
         ************************************************************/
        async function loadModels() {
            try {
                console.log("🔄 Loading face-api.js models...");
                const modelUrl = '{{ asset("models") }}';
                await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl);
                await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl);
                await faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl);

                modelsLoaded = true;
                console.log("✅ Models loaded.");
            } catch (error) {
                console.error("❌ Model load error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Model Load Error',
                    text: 'Check the console for details.'
                });
            }
        }

        /************************************************************
         * 3) FETCH USERS & BUILD FACE MATCHER (CACHED)
         ************************************************************/
        async function fetchUsersAndBuildDescriptors() {
            try {
                if (descriptorsCached) {
                    console.log("Descriptors already cached. Skipping fetch.");
                    return;
                }

                console.log("🔄 Fetching user avatars...");
                const response = await fetch('{{ url("api/users/avatar") }}');
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                const { data: users } = await response.json();

                for (const user of users) {
                    let avatarUrls = [];
                    if (Array.isArray(user.avatars)) {
                        avatarUrls = user.avatars;
                    } else if (user.avatar) {
                        avatarUrls = [user.avatar];
                    } else {
                        console.warn(`No avatar found for user ${user.id}`);
                        continue;
                    }

                    const descriptors = [];
                    for (const url of avatarUrls) {
                        try {
                            const img = new Image();
                            img.crossOrigin = 'anonymous';
                            img.src = url;
                            await new Promise((resolve, reject) => {
                                img.onload = resolve;
                                img.onerror = () => reject(new Error(`Failed to load avatar ${url}`));
                            });

                            const detection = await faceapi
                                .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptor();

                            if (detection && detection.descriptor) {
                                descriptors.push(detection.descriptor);
                            }
                        } catch (imgErr) {
                            console.error(`Error loading avatar ${url} for user ${user.id}`, imgErr);
                        }
                    }

                    if (descriptors.length > 0) {
                        const label = `${user.id}-${(user.name || 'Unknown').replace(/\s+/g, '_')}`;
                        labeledDescriptors.push(
                            new faceapi.LabeledFaceDescriptors(label, descriptors)
                        );
                    }
                }

                if (labeledDescriptors.length > 0) {
                    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.60);
                    console.log("✅ FaceMatcher created.");
                } else {
                    console.warn("No labeled descriptors. Recognition won't work.");
                }

                descriptorsCached = true;
            } catch (error) {
                console.error("❌ Fetch user error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'User Fetch Error',
                    text: 'Check console for details.'
                });
            }
        }

        /************************************************************
         * 4) LIVENESS CHECK (BLINK / HEAD MOVE)
         *    - Here we just do a simple "blink" attempt
         ************************************************************/
        async function ensureLiveness() {
            Swal.fire({
                title: "Liveness Check",
                text: "Please blink or move your head slightly to confirm you're real.",
                icon: "info",
                timer: 5000
            });

            const video = document.getElementById('video');
            let isLivenessDetected = false;

            // We'll attempt to detect a face multiple times
            // If we find it once, we assume they moved/blinked
            for (let i = 0; i < 5; i++) {
                const detection = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                // If detection occurs at least once, we consider it "liveness"
                if (detection) {
                    isLivenessDetected = true;
                    break;
                }

                // Wait a bit and re-check
                await new Promise(resolve => setTimeout(resolve, 1000));
            }

            if (!isLivenessDetected) {
                Swal.fire({
                    icon: 'error',
                    title: 'Liveness Check Failed',
                    text: 'Face not detected properly. Please try again.'
                });
                return false;
            }

            return true;
        }

        /************************************************************
         * 5) SINGLE-SHOT DETECTION (Multiple People)
         *    - With detection frequency limit
         ************************************************************/
        async function recognizeFacesFromVideo() {
            if (!modelsLoaded) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Models Not Loaded',
                    text: 'Wait for face-api to finish loading.'
                });
                return [];
            }

            if (!faceMatcher) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Face Data',
                    text: 'No labeled descriptors available.'
                });
                return [];
            }

            // Frequency limit
            const now = Date.now();
            if (now - lastDetectionTime < DETECTION_COOLDOWN_MS) {
                console.log("Skipping detection: too frequent.");
                return [];
            }
            lastDetectionTime = now;

            const video = document.getElementById('video');
            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (detections.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Face Detected',
                    text: 'Try better lighting or positioning.'
                });
                return [];
            }

            let recognizedUsers = [];

            for (let d of detections) {
                const bestMatch = faceMatcher.findBestMatch(d.descriptor);

                if (bestMatch.label !== 'unknown') {
                    const [userId, ...rest] = bestMatch.label.split('-');
                    const userName = rest.join(' ').replace(/_/g, ' ');

                    // Avoid duplicates
                    if (!recognizedUsers.some(u => u.userId === userId)) {
                        recognizedUsers.push({ userId, userName });
                    }
                }
            }

            if (recognizedUsers.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Match Found',
                    text: 'No known users matched.'
                });
            }

            return recognizedUsers;
        }

        /************************************************************
         * 6) CHECK IN
         *    - Combines liveness + face recognition
         ************************************************************/
        async function checkIn() {
            const isAlive = await ensureLiveness();
            if (!isAlive) return;  // Liveness check failed

            const recognizedUsers = await recognizeFacesFromVideo();
            if (!recognizedUsers || recognizedUsers.length === 0) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            for (const user of recognizedUsers) {
                const now = new Date();
                const payload = {
                    worker_id: user.userId,
                    date: now.toISOString().split('T')[0],
                    date_out: null,
                    in_time: now.toTimeString().split(' ')[0],
                    out_time: null,
                    in_location_id: 1,
                    out_location_id: null
                };

                try {
                    console.log(`📤 Sending Check-In for ${user.userName}:`, payload);

                    const response = await fetch('{{ url("api/attendances") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        if (response.status === 409) {
                            const msg = await response.json();
                            Swal.fire({
                                icon: 'warning',
                                title: 'Already Checked In',
                                text: `${user.userName} has already checked in today.`
                            });
                        } else {
                            throw new Error(`HTTP Error ${response.status}`);
                        }
                        continue;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: `Welcome, ${user.userName}!`,
                        text: 'Check-In recorded successfully.'
                    });

                } catch (error) {
                    console.error(`❌ Check-In Error for ${user.userName}:`, error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Check-In Failed',
                        text: `Failed to check in ${user.userName}.`
                    });
                }
            }
        }

        /************************************************************
         * 7) CHECK OUT
         *    - Combines liveness + face recognition
         ************************************************************/
        async function checkOut() {
            const isAlive = await ensureLiveness();
            if (!isAlive) return;  // Liveness check failed

            const recognizedUsers = await recognizeFacesFromVideo();
            if (!recognizedUsers || recognizedUsers.length === 0) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            for (const user of recognizedUsers) {
                const now = new Date();
                const payload = {
                    worker_id: user.userId,
                    date: now.toISOString().split('T')[0],
                    out_time: now.toTimeString().split(' ')[0]
                };

                try {
                    console.log(`📤 Sending Check-Out for ${user.userName}:`, payload);

                    const response = await fetch('{{ url("api/attendances/checkout") }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        if (response.status === 404) {
                            const msg = await response.json();
                            Swal.fire({
                                icon: 'warning',
                                title: 'Check Out Error',
                                text: `${user.userName}: ${msg.message || 'No attendance record found to update.'}`
                            });
                        } else {
                            throw new Error(`HTTP Error ${response.status}`);
                        }
                        continue;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: `Goodbye, ${user.userName}!`,
                        text: 'Check-Out recorded successfully.'
                    });

                } catch (error) {
                    console.error(`❌ Check-Out Error for ${user.userName}:`, error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Check-Out Failed',
                        text: `Failed to check out ${user.userName}.`
                    });
                }
            }
        }

        /************************************************************
         * 8) LIVE TRACKER OVERLAY (Optional)
         *    - Draws bounding boxes in real-time
         ************************************************************/
        function startLiveTracker() {
            const video = document.getElementById('video');
            const container = document.getElementById('cameraContainer');

            // Create an overlay canvas
            const canvas = faceapi.createCanvasFromMedia(video);
            container.append(canvas);

            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            liveTrackerInterval = setInterval(async () => {
                if (!modelsLoaded || !faceMatcher) return;

                const detections = await faceapi
                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                // Resize
                const resized = faceapi.resizeResults(detections, displaySize);
                // Clear
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                resized.forEach(d => {
                    const bestMatch = faceMatcher.findBestMatch(d.descriptor);
                    let label = bestMatch.label === 'unknown' ? 'Unknown' : bestMatch.label;
                    // e.g., "10-Yaman_Alali"
                    if (label !== 'Unknown') {
                        const [userId, ...rest] = label.split('-');
                        label = rest.join(' ').replace(/_/g, ' ');
                    }

                    const drawBox = new faceapi.draw.DrawBox(d.detection.box, { label });
                    drawBox.draw(canvas);
                });
            }, 300);
        }

        /************************************************************
         * 9) On DOM Loaded => INIT
         ************************************************************/
        document.addEventListener('DOMContentLoaded', async () => {
            await startCamera();
            await loadModels();
            await fetchUsersAndBuildDescriptors();
            // Start live overlay if faceMatcher is ready
            if (faceMatcher) {
                startLiveTracker();
            }
        });

        /************************************************************
         * 10) Button Event Listeners
         ************************************************************/
        document.getElementById('checkInButton').addEventListener('click', checkIn);
        document.getElementById('checkOutButton').addEventListener('click', checkOut);
    </script>
@stop
