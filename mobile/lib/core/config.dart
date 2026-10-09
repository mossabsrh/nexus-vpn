/// App configuration.
///
/// The API address can be overridden at run time (e.g. to point at your PC's
/// LAN IP when testing on a real phone):
///   flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000
class AppConfig {
  /// Origin of the Laravel backend — no trailing slash, no `/api`.
  /// Default `10.0.2.2` is the Android emulator's alias for the host machine's localhost.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000',
  );

  /// REST base. Mobile endpoints live under `/api/mobile`, public ones under `/api`.
  static String get apiUrl => '$apiBaseUrl/api';
}
