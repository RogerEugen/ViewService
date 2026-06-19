# ViewService - Frontend & View Layer Microservice

## Overview

**ViewService** is a Vue.js-based frontend and view layer microservice that serves as the user-facing component of a microservice architecture. It handles the presentation layer, user interface, and communication with the AuthValid authentication service.

## Project Type
**Final Year Project** - Microservice Architecture Implementation

## Key Features

- **Modern Vue.js Frontend** - Interactive user interface built with Vue 3
- **Responsive Design** - Tailwind CSS for responsive and modern styling
- **API Integration** - Communicates with AuthValid microservice for authentication
- **TypeScript Support** - Full TypeScript configuration for type-safe development
- **Build Optimization** - Vite for fast development and optimized builds
- **Code Quality** - ESLint and Prettier for consistent code standards

## Technology Stack

- **Frontend Framework:** Vue 3 (Vue.js)
- **Styling:** Tailwind CSS
- **Language:** JavaScript/TypeScript
- **Build Tool:** Vite
- **Code Quality:** ESLint, Prettier
- **Package Manager:** NPM
- **Testing:** PHPUnit (Laravel integration)
- **Backend Support:** Laravel (API communication)

## Project Structure

```
ViewService/
├── app/                 # Laravel backend logic (if applicable)
├── resources/           # Vue components and assets
├── public/             # Publicly accessible files
├── config/             # Application configuration
├── database/           # Database migrations (if applicable)
├── routes/             # API route definitions
├── bootstrap/          # Bootstrap files
├── storage/            # File storage
├── tests/              # Test cases
├── .github/            # GitHub workflows and configurations
├── node_modules/       # NPM dependencies (generated)
├── package.json        # NPM dependencies & scripts
├── composer.json       # PHP dependencies
├── vite.config.js      # Vite configuration
├── vite.config.ts      # Vite TypeScript configuration
├── tsconfig.json       # TypeScript configuration
├── tailwind.config.js  # Tailwind CSS configuration
├── postcss.config.js   # PostCSS configuration
├── eslint.config.js    # ESLint configuration
├── .prettierrc          # Prettier configuration
└── .env.example        # Environment variables template
```

## Installation & Setup

### Prerequisites
- Node.js >= 16.x
- NPM >= 8.x
- PHP >= 8.0 (for backend support)
- Composer (for backend dependencies)

### Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/RogerEugen/ViewService.git
   cd ViewService
   ```

2. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

3. **Install PHP dependencies (if using backend):**
   ```bash
   composer install
   ```

4. **Environment Configuration:**
   ```bash
   cp .env.example .env
   ```

5. **Configure API Endpoints:**
   - Update `.env` with AuthValid service URL
   - Configure authentication endpoints

6. **Start Development Server:**
   ```bash
   npm run dev
   ```
   - Frontend will be available at `http://localhost:5173`

7. **Build for Production:**
   ```bash
   npm run build
   ```

## Available Scripts

### Development
- `npm run dev` - Start Vite development server with hot reload
- `npm run preview` - Preview production build locally

### Production
- `npm run build` - Create optimized production build
- `npm run build:docs` - Build documentation (if applicable)

### Code Quality
- `npm run lint` - Run ESLint checks
- `npm run format` - Format code with Prettier

## Integration with AuthValid

ViewService communicates with **AuthValid** for:
- User authentication and login
- User validation
- Session management
- Authorization checks

**Communication Flow:**
```
ViewService (Frontend)
    ↓
AuthValid API (Backend Authentication)
    ↓
Database/User Validation
```

## Vue.js Components

The application uses Vue 3 Composition API with the following structure:
- Reusable components in `resources/components/`
- Page views in `resources/views/`
- Store management for state (if using Pinia)
- API service layer for backend communication

## Styling

- **Tailwind CSS** for utility-first styling
- **PostCSS** for CSS transformations
- Responsive design patterns
- Dark mode support (if configured)

## TypeScript

Full TypeScript support is configured:
- Type-safe component development
- Better IDE autocomplete
- Runtime type checking

## Code Standards

### Formatting
- Prettier for automatic code formatting
- Run `npm run format` before committing

### Linting
- ESLint for code quality
- Run `npm run lint` to check for issues

## Development Workflow

1. **Feature Development:**
   - Create a new branch from main
   - Develop feature with hot reload (`npm run dev`)
   - Follow ESLint rules and Prettier formatting

2. **Testing:**
   - Test component functionality
   - Verify API integration with AuthValid

3. **Build & Deploy:**
   - Build production bundle: `npm run build`
   - Deploy built files from `dist/` directory

4. **Pull Request:**
   - Ensure linting passes
   - Submit PR for review

## Environment Variables

Key environment variables in `.env`:
- `VITE_API_URL` - AuthValid API endpoint
- `VITE_API_TIMEOUT` - API request timeout
- Other service-specific configurations

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Performance Optimization

- **Vite** provides fast HMR (Hot Module Replacement)
- **Tree-shaking** for optimized bundle size
- **Code splitting** for better caching
- **Image optimization** best practices

## Contributing

1. Create a feature branch
2. Run `npm run lint` and `npm run format`
3. Commit changes with meaningful messages
4. Push to repository
5. Create a Pull Request

## Project Authors
**RogerEugen** - Final Year Project

## License
[To be specified]

## Status
🚀 **In Development** - Final Year Project

---

**Related Service:** [AuthValid](https://github.com/RogerEugen/AuthValid)

## Support

For issues, questions, or contributions, please open a GitHub issue or contact the project maintainers.
