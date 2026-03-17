import app from './app.js';
import { env } from './config/index';

app.listen(env.PORT, () => {
  console.log(`Backend funcionando en http://localhost:${env.PORT}`);
});
