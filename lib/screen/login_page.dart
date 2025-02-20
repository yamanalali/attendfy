import 'package:attendancewithfingerprint/database/db_helper.dart';
import 'package:attendancewithfingerprint/screen/main_menu_page.dart';
import 'package:attendancewithfingerprint/utils/strings.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:progress_dialog_null_safe/progress_dialog_null_safe.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  LoginPageState createState() => LoginPageState();
}

enum LoginStatus { notSignIn, signIn, doubleCheck }

class LoginPageState extends State<LoginPage> {
  LoginStatus _loginStatus = LoginStatus.notSignIn;
  String? email, pass, isLogged, getUrl, getKey;
  String statusLogged = 'logged';
  String getPath = '/api/login';
  final _key = GlobalKey<FormState>();
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  bool _secureText = true;

  // Progress dialog
  late ProgressDialog pr;

  // Database
  DbHelper dbHelper = DbHelper();

  @override
  void initState() {
    getSettings();
    super.initState();
    // First init
    // Check is user is logged, or not
  }

  // Function show or not the password
  void showHide() {
    setState(() {
      _secureText = !_secureText;
    });
    return;
  }

  // Check if all data is ok, will submit the form via API
  void check() {
    final form = _key.currentState!;
    if (kDebugMode) {
      print(form.validate());
    }
    if (form.validate()) {
      form.save();
      login('clickButton');
    }
    return;
  }

  // Get settings data
  Future<void> getSettings() async {
    final getSettings = await dbHelper.getSettings(1);
    setState(() {
      getUrl = getSettings?.url;
      getKey = getSettings?.key;

      getPref();
    });
  }

  // Function communicate with the server via API
  Future<void> login(String fromWhere) async {
    final urlLogin = getUrl! + getPath;
    if (fromWhere == 'clickButton') await pr.show();

    final Dio dio = Dio();
    final FormData formData =
        FormData.fromMap({"email": email, "password": pass});
    final response = await dio.post(urlLogin, data: formData);

    // Return the json data
    final dynamic data = response.data;

    // Get the message data from json
    final message = data['message'].toString();

    // Check if success
    if (message == "success") {
      final token = data['token'].toString();
      final role = int.parse(data['user']['role'].toString());

      if (role == 1 || role == 4) {
        setState(() {
          getSnackBar(loginWrongRole);
        });
      } else {
        isLogged = statusLogged;
        final email = data['user']['email'].toString();
        final userId = int.parse(data['user']['id'].toString());

        setState(() {
          _loginStatus = LoginStatus.signIn;
          savePref(isLogged, email, pass, userId, token);
        });
      }
    } else {
      // Password and email wrong
      if (fromWhere == 'clickButton') {
        setState(() {
          getSnackBar(loginFailed);
        });
      } else {
        // Before correct email and password, but maybe user change the email and password
        setState(() {
          getSnackBar(loginDoubleCheck);
          _loginStatus = LoginStatus.notSignIn;
          removePref();
        });
      }
    }

    // Hide the loading
    Future.delayed(Duration.zero).then((value) async {
      if (mounted) {
        if (fromWhere == 'clickButton') await pr.hide();
      }
    });

    return;
  }

  Future<void> removePref() async {
    final preferences = await SharedPreferences.getInstance();
    preferences.remove("status");
    preferences.remove("email");
    preferences.remove("password");
    preferences.remove("id");
    preferences.remove("token");
    return;
  }

  // Show snackBar
  ScaffoldFeatureController<SnackBar, SnackBarClosedReason> getSnackBar(
    String messageSnackBar,
  ) {
    return ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(messageSnackBar)));
  }

  // Save the data from json data
  Future<void> savePref(
    String? getStatus,
    String? getEmployeeId,
    String? getPassword,
    int? getUserId,
    String? token,
  ) async {
    final SharedPreferences preferences = await SharedPreferences.getInstance();
    setState(() {
      preferences.setString("status", getStatus!);
      preferences.setString("email", getEmployeeId!);
      preferences.setString("password", getPassword!);
      preferences.setInt("id", getUserId!);
      preferences.setString("token", token!);
    });
    return;
  }

  // Check is there any data at Shared Preferences, is any data, means user logged
  Future<void> getPref() async {
    final SharedPreferences preferences = await SharedPreferences.getInstance();
    setState(() {
      final getStatusSp = preferences.getString("status");
      final getEmail = preferences.getString("email");
      final getPassword = preferences.getString("password");

      if (getStatusSp == statusLogged) {
        _loginStatus = LoginStatus.doubleCheck;
        // if user already login, will check again, if there is any change on web server
        // Like change the role, or the status
        email = getEmail;
        pass = getPassword;
        login('doubleCheck');
      } else {
        _loginStatus = LoginStatus.notSignIn;
      }
    });
    return;
  }

  @override
  // ignore: missing_return
  Widget build(BuildContext context) {
    // Show progress
    pr = ProgressDialog(context, type: ProgressDialogType.normal);
    // Style progress
    pr.style(
      message: loginCheckingProgress,
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

    switch (_loginStatus) {
      case LoginStatus.notSignIn:
        return Scaffold(
          key: _scaffoldKey,
          backgroundColor: const Color(0xFF0E67B4),
          body: SafeArea(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Column(
                  children: [
                    Container(
                      margin: const EdgeInsets.only(top: 100.0),
                      child: const Image(
                        image: AssetImage('images/logo_trans.png'),
                      ),
                    ),
                    const SizedBox(
                      height: 20.0,
                    ),
                    Text(
                      loginSubtitle,
                      textAlign: TextAlign.center,
                      style: GoogleFonts.quicksand(
                        color: Colors.white,
                        fontSize: 15.0,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                Expanded(
                  child: Container(
                    margin: const EdgeInsets.fromLTRB(20.0, 0.0, 20.0, 0.0),
                    child: Form(
                      key: _key,
                      child: ListView(
                        padding: const EdgeInsets.all(16.0),
                        children: <Widget>[
                          inputLogin(
                            isEmail: true,
                            email,
                            loginLabelEmail,
                            loginEmptyEmail,
                          ),
                          const SizedBox(height: 20),
                          inputLogin(
                            pass,
                            loginLabelPassword,
                            loginEmptyPassword,
                            isEmail: false,
                          ),
                          Container(
                            margin: const EdgeInsets.only(top: 20.0),
                            width: double.infinity,
                            height: 60,
                            child: ElevatedButton(
                              onPressed: () {
                                _key.currentState!.save();
                                FocusScope.of(context)
                                    .requestFocus(FocusNode());
                                check();
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF003D84),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(18.0),
                                  side: const BorderSide(
                                    color: Color(0xFF003D84),
                                  ),
                                ),
                              ),
                              child: const Text(
                                loginButton,
                                style: TextStyle(color: Colors.white),
                              ),
                            ),
                          ),
                          const SizedBox(
                            height: 10.0,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      case LoginStatus.signIn:
        return const MainMenuPage();
      case LoginStatus.doubleCheck:
        return Scaffold(
          backgroundColor: Colors.blue,
          key: _scaffoldKey,
          body: Container(
            color: const Color(0xFF0E67B4),
            child: const Center(
              child: Image(
                image: AssetImage('images/logo_color.png'),
              ),
            ),
          ),
        );
    }
  }

  TextFormField inputLogin(
    String? value,
    String label,
    String messageError, {
    required bool isEmail,
  }) {
    return TextFormField(
      style: GoogleFonts.quicksand(color: Colors.white),
      validator: (e) {
        if (e!.isEmpty) {
          if (isEmail) return "Email cannot be empty.";
          if (!isEmail) return "Password cannot be empty.";
        }
        return null;
      },
      onSaved: (e) {
        if (isEmail) email = e;
        if (!isEmail) pass = e;
      },
      obscureText: !isEmail && _secureText,
      decoration: InputDecoration(
        labelText: label,
        hintStyle: GoogleFonts.quicksand(color: Colors.white),
        labelStyle: const TextStyle(color: Colors.white),
        suffixIcon: !isEmail
            ? IconButton(
                onPressed: showHide,
                color: Colors.white,
                icon:
                    Icon(_secureText ? Icons.visibility_off : Icons.visibility),
              )
            : null,
        focusedBorder: OutlineInputBorder(
          borderSide: const BorderSide(color: Colors.white, width: 2.0),
          borderRadius: BorderRadius.circular(25.0),
        ),
        enabledBorder: OutlineInputBorder(
          borderSide: const BorderSide(
            color: Color(0xFFDBE2E7),
            width: 2,
          ),
          borderRadius: BorderRadius.circular(8),
        ),
        errorStyle: const TextStyle(color: Color.fromARGB(255, 255, 122, 133)),
      ),
    );
  }
}
