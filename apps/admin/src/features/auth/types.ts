export type AdminLoginRequest = {
  readonly email: string;
  readonly password: string;
};

export type AdminLoginResponse = {
  readonly accessToken: string;
  readonly refreshToken: string;
};
