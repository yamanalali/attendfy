import 'dart:async';

import 'package:attendancewithfingerprint/model/attendance.dart';
import 'package:attendancewithfingerprint/screen/main_menu_page.dart';
import 'package:attendancewithfingerprint/utils/strings.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:local_auth/local_auth.dart';
import 'package:progress_dialog_null_safe/progress_dialog_null_safe.dart';
import 'package:rflutter_alert/rflutter_alert.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:trust_fall/trust_fall.dart';

import '../database/db_helper.dart';
import '../model/settings.dart';
import '../utils/utils.dart';

class AttendancePage extends StatefulWidget {
  final String? query;
  final String? title;

  const AttendancePage({super.key, this.query, this.title});

  @override
  AttendancePageState createState() => AttendancePageState();
}

class AttendancePageState extends State<AttendancePage> {
  // Progress dialog
  late ProgressDialog pr;

  final LocalAuthentication _localAuthentication = LocalAuthentication();

  // Database
  DbHelper dbHelper = DbHelper();

  // Utils
  Utils utils = Utils();

  // Model settings
  Settings? settings;

  // Global key scaffold
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  // String
  String? getUrl,
      getKey,
      getQrId,
      getQuery,
      getPath = 'api/attendance/apiSaveAttendance',
      mAccuracy,
      getPathArea = 'api/area/index',
      token;

  dynamic getId, _value;
  bool? _isMockLocation, clickButton = false;

  // Geolocation
  late Position _currentPosition;
  final Geolocator geoLocator = Geolocator();
  late dynamic subscription;
  double setAccuracy = 200.0;

  // Arr area
  List? dataArea = [];

  @override
  void initState() {
    super.initState();
    getPref();
  }

  // Check is there any data at Shared Preferences, is any data, means user logged
  Future<void> getPref() async {
    final SharedPreferences preferences = await SharedPreferences.getInstance();
    setState(() {
      getId = preferences.getInt("id");
      token = preferences.getString("token");
    });

    // Get current loc
    _getCurrentLocation();
  }

  // Get latitude longitude
  void _getCurrentLocation() {
    subscription = Geolocator.getPositionStream().listen((position) {
      if (mounted) {
        setState(() {
          _currentPosition = position;
        });

        _getAccuracyFromLatLng(_currentPosition.accuracy);
      }
    });

    // Check fake gps
    checkMockLoc();
    // Do settings
    getSettings();
  }

  // Get address
  Future<void> _getAccuracyFromLatLng(double accuracy) async {
    final String strAccuracy = accuracy.toStringAsFixed(1);
    if (accuracy > setAccuracy) {
      mAccuracy = '$strAccuracy $attendanceNotAccurate';
    } else {
      mAccuracy = '$strAccuracy $attendanceAccurate';
    }
  }

  // Checking Mock (fake GPS)
  Future<void> checkMockLoc() async {
    try {
      _isMockLocation = await TrustFall.canMockLocation;
    } catch (error) {
      if (kDebugMode) {
        print(error);
      }
    }
  }

  // Get settings data
  Future<void> getSettings() async {
    final getSettings = await dbHelper.getSettings(1);
    setState(() {
      getUrl = getSettings?.url;
      getKey = getSettings?.key;
    });

    getAreaApi();
  }

  // Getting area via API
  Future<void> getAreaApi() async {
    await pr.show();
    final uri = utils.getRealUrl(getUrl!, getPathArea);
    final Dio dio = contactServerViaDio();
    final response = await dio.get(uri);

    final data = response.data;

    if (data['message'] == 'success') {
      setState(() {
        dataArea = data['area'] as List;
      });

      await pr.hide();
    } else {
      dataArea = [
        {"id": 0, "name": "No Data Area"},
      ];
    }
  }

  // Add header here
  Dio contactServerViaDio() {
    final Dio dio = Dio();
    dio.options.headers['content-Type'] = 'application/json';
    dio.options.headers["authorization"] = "Bearer ${token!}";
    return dio;
  }

  // Send data post via http
  Future<void> sendData() async {
    await pr.show();

    // Get info for attendance
    final dataKey = getKey;
    final dataQuery = getQuery;

    // Add data to map
    final Map<String, dynamic> body = {
      'key': dataKey,
      'worker_id': getId,
      'q': dataQuery,
      'lat': _currentPosition.latitude,
      'longt': _currentPosition.longitude,
      'area_id': _value,
    };

    // Sending the data to server
    final uri = utils.getRealUrl(getUrl!, getPath);
    final Dio dio = contactServerViaDio();

    final FormData formData = FormData.fromMap(body);
    final response = await dio.post(uri, data: formData);

    final data = response.data;

    // Show response from server via snackBar
    if (data['message'] == 'Success!') {
      // Set the url and key
      final Attendance attendance = Attendance(
        date: data['date'].toString(),
        time: data['time'].toString(),
        location: data['location'].toString(),
        type: data['query'].toString(),
      );

      // Insert the settings
      insertAttendance(attendance);

      // Hide the loading
      Future.delayed(Duration.zero).then((value) async {
        if (mounted) {
          subscription.cancel();
          await pr.hide();
          Alert(
            context: _scaffoldKey.currentContext!,
            type: AlertType.success,
            title: "Success",
            desc: "$attendanceShowAlert-$dataQuery $attendanceSuccessMs",
            buttons: [
              DialogButton(
                onPressed: () => Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const MainMenuPage(),
                  ),
                  (Route<dynamic> route) => false,
                ),
                width: 120,
                child: const Text(
                  okText,
                  style: TextStyle(color: Colors.white, fontSize: 20),
                ),
              ),
            ],
          ).show();
        }
      });
    } else if (data['message'] == 'cannot attend') {
      Future.delayed(Duration.zero).then((value) async {
        await pr.hide();

        utils.showAlertDialog(
          outsideArea,
          "warning",
          AlertType.warning,
          _scaffoldKey,
          isAnyButton: true,
        );
      });
    } else if (data['message'] == 'location not found') {
      Future.delayed(Duration.zero).then((value) async {
        await pr.hide();

        utils.showAlertDialog(
          locationNotFound,
          "warning",
          AlertType.warning,
          _scaffoldKey,
          isAnyButton: true,
        );
      });
    } else if (data['message'] == 'already check-in') {
      Future.delayed(Duration.zero).then((value) async {
        await pr.hide();

        utils.showAlertDialog(
          alreadyCheckIn,
          "warning",
          AlertType.warning,
          _scaffoldKey,
          isAnyButton: true,
        );
      });
    } else if (data['message'] == 'check-in first') {
      Future.delayed(Duration.zero).then((value) async {
        await pr.hide();

        utils.showAlertDialog(
          checkInFirst,
          "warning",
          AlertType.warning,
          _scaffoldKey,
          isAnyButton: true,
        );
      });
    } else if (data['message'] == 'Error! Something Went Wrong!') {
      Future.delayed(Duration.zero).then((value) async {
        await pr.hide();

        utils.showAlertDialog(
          attendanceErrorServer,
          "Error",
          AlertType.error,
          _scaffoldKey,
          isAnyButton: true,
        );
      });
    } else {
      Future.delayed(Duration.zero).then((value) async {
        await pr.hide();

        utils.showAlertDialog(
          data['message'],
          "Error",
          AlertType.error,
          _scaffoldKey,
          isAnyButton: true,
        );
      });
    }
  }

  Future<void> insertAttendance(Attendance object) async {
    await dbHelper.newAttendances(object);
  }

  // To check if any type of biometric authentication
  // hardware is available.
  Future<bool> _isBiometricAvailable() async {
    bool isAvailable = false;
    try {
      isAvailable = await _localAuthentication.canCheckBiometrics;
    } on PlatformException catch (e) {
      if (kDebugMode) {
        print(e);
      }
    }

    if (!mounted) return isAvailable;

    return isAvailable;
  }

  // To retrieve the list of biometric types
  // (if available).
  Future<void> _getListOfBiometricTypes() async {
    try {
      await _localAuthentication.getAvailableBiometrics();
    } on PlatformException catch (e) {
      if (kDebugMode) {
        print(e);
      }
    }

    if (!mounted) return;
  }

  // Process of authentication user using
  // biometrics.
  Future<void> _authenticateUser() async {
    bool isAuthenticated = false;
    try {
      isAuthenticated = await _localAuthentication.authenticate(
        localizedReason: "Authenticate to attending",
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );
    } on PlatformException catch (e) {
      if (kDebugMode) {
        print(e);
      }
      Future.delayed(Duration.zero).then((value) {
        setState(() {
          pr.hide();

          utils.showAlertDialog(
            emptyFingerprint,
            "warning",
            AlertType.warning,
            _scaffoldKey,
            isAnyButton: true,
          );
        });
      });

      return;
    }

    if (!mounted) return;

    if (isAuthenticated) {
      sendData();
    }
  }

  // This function is about checking if the user uses Mock (Fake GPS) to make attendance
  Future<void> checkMockIsNull() async {
    // Check if user click button attendance
    if (clickButton!) {
      // Check mock is already get status
      if (_isMockLocation == null) {
        Future.delayed(Duration.zero).then((value) async {
          // Check if pr is showing or not
          if (!pr.isShowing()) {
            await pr.show();
            pr.update(
              progress: 50.0,
              message: checkMock,
              progressWidget: Container(
                padding: const EdgeInsets.all(8.0),
                child: const CircularProgressIndicator(),
              ),
              maxProgress: 100.0,
              progressTextStyle: const TextStyle(
                color: Colors.black,
                fontSize: 13.0,
                fontWeight: FontWeight.w400,
              ),
              messageTextStyle: const TextStyle(
                color: Colors.black,
                fontSize: 19.0,
                fontWeight: FontWeight.w600,
              ),
            );
          }
        });
      } else if (_isMockLocation == true) {
        // If there user use mock location means uses fake gps
        // Will show warning alert
        Future.delayed(Duration.zero).then((value) async {
          // Detect mock is true, mean user use fake gps
          setState(() {
            clickButton = false;
          });

          if (pr.isShowing()) {
            await pr.hide();
          }

          utils.showAlertDialog(
            fakeGps,
            "warning",
            AlertType.warning,
            _scaffoldKey,
            isAnyButton: true,
          );
        });
      } else {
        // If user not use fake gps
        // Continue to check area location if already choosed
        Future.delayed(Duration.zero).then((value) async {
          setState(() {
            clickButton = false;
          });

          if (pr.isShowing()) {
            await pr.hide();
          }

          // Check if area is not empty
          if (_value == null) {
            Future.delayed(Duration.zero).then((value) async {
              await pr.hide();

              utils.showAlertDialog(
                selectArea,
                "warning",
                AlertType.warning,
                _scaffoldKey,
                isAnyButton: true,
              );
            });

            return;
          }

          // If already get mock will continue show biometric
          if (await _isBiometricAvailable()) {
            await _getListOfBiometricTypes();
            await _authenticateUser();
          } else {
            Future.delayed(Duration.zero).then((value) async {
              await pr.hide();

              utils.showAlertDialog(
                notSupportFingerprint,
                "warning",
                AlertType.warning,
                _scaffoldKey,
                isAnyButton: true,
              );
            });

            return;
          }
        });
      }
    }
  }

  // Show snackBar
  ScaffoldFeatureController<SnackBar, SnackBarClosedReason> getSnackBar(
    String messageSnackBar,
  ) {
    return ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(messageSnackBar)));
  }

  @override
  Widget build(BuildContext context) {
    // Show progress
    pr = ProgressDialog(
      context,
      isDismissible: false,
      type: ProgressDialogType.normal,
    );
    // Style progress
    pr.style(
      message: attendanceSending,
      borderRadius: 10.0,
      backgroundColor: Colors.white,
      progressWidget: const CircularProgressIndicator(),
      elevation: 10.0,
      padding: const EdgeInsets.all(10.0),
      insetAnimCurve: Curves.easeInOut,
      progress: 0.0,
      maxProgress: 100.0,
      progressTextStyle: const TextStyle(
        color: Colors.black,
        fontSize: 13.0,
        fontWeight: FontWeight.w400,
      ),
      messageTextStyle: const TextStyle(
        color: Colors.black,
        fontSize: 19.0,
        fontWeight: FontWeight.w600,
      ),
    );

    // Init the query
    getQuery = widget.query;

    // Check if user use fake gps
    checkMockIsNull();

    return Scaffold(
      backgroundColor: const Color(0xFFF1F4F8),
      key: _scaffoldKey,
      appBar: AppBar(
        title: Text(widget.title!),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: <Widget>[
            Container(
              margin: const EdgeInsets.fromLTRB(60.0, 20.0, 60.0, 20.0),
              child: Column(
                children: [
                  Text(
                    attendanceSelectArea,
                    style: GoogleFonts.quicksand(
                      color: Colors.grey[600],
                      fontSize: 16.0,
                      fontWeight: FontWeight.w600,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  DropdownButton(
                    items: dataArea!.map((item) {
                      return DropdownMenuItem(
                        value: item['id'].toString(),
                        child: Text(item['name'].toString()),
                      );
                    }).toList(),
                    onChanged: (dynamic newVal) {
                      setState(() {
                        _value = newVal;
                      });
                    },
                    value: _value,
                    isExpanded: true,
                  ),
                ],
              ),
            ),
            Container(
              margin: const EdgeInsets.all(20.0),
              width: double.infinity,
              height: 60.0,
              child: ElevatedButton(
                onPressed: () {
                  clickButton = true;
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF003D84),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(18.0),
                  ),
                ),
                child: Text(
                  buttonScanAttend + getQuery!,
                  style: const TextStyle(color: Colors.white),
                ),
              ),
            ),
            Text(
              '$attendanceButtonInfo-$getQuery.',
              style: GoogleFonts.quicksand(color: Colors.grey, fontSize: 12.0),
            ),
            const SizedBox(
              height: 20.0,
            ),
            Text(
              '$attendanceAccurateInfo $mAccuracy $attendanceOnGps',
              style: GoogleFonts.quicksand(
                color: Colors.grey[800],
                fontSize: 14.0,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
